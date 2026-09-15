<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Support\FeeAccountService;
use App\Support\PaymentReferenceGenerator;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    private function nextReceiptNumber(): string
    {
        return PaymentReferenceGenerator::generateReceiptNumber();
    }

    private function guardianName(?string $first, ?string $last): ?string
    {
        $name = trim(($first ?? '') . ' ' . ($last ?? ''));
        return $name !== '' ? $name : null;
    }

    /* ── index ────────────────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $q = DB::table('finance_payments as fp')
            ->join('students', 'fp.student_id', '=', 'students.id')
            ->join('academic_years', 'fp.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'fp.term_id', '=', 'terms.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->leftJoin('guardians', 'fp.guardian_id', '=', 'guardians.id')
            ->select(
                'fp.*',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.admission_number',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'users.name as received_by_name',
                'guardians.first_name as guardian_first_name',
                'guardians.last_name as guardian_last_name'
            )
            ->orderByDesc('fp.payment_date')
            ->orderByDesc('fp.id');

        if ($request->student_id)       $q->where('fp.student_id', $request->student_id);
        if ($request->academic_year_id) $q->where('fp.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('fp.term_id', $request->term_id);
        if ($request->payment_method)   $q->where('fp.payment_method', $request->payment_method);
        if ($request->date_from)        $q->where('fp.payment_date', '>=', $request->date_from);
        if ($request->date_to)          $q->where('fp.payment_date', '<=', $request->date_to);
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $q->where(function ($x) use ($s) {
                $x->where('students.first_name', 'like', $s)
                  ->orWhere('students.last_name', 'like', $s)
                  ->orWhere('students.student_number', 'like', $s)
                  ->orWhere('students.admission_number', 'like', $s)
                  ->orWhere('fp.receipt_number', 'like', $s);
            });
        }

        $perPage = (int)($request->per_page ?? 20);
        $page    = (int)($request->page ?? 1);
        $total   = (clone $q)->count();
        $items   = $q->offset(($page - 1) * $perPage)->limit($perPage)->get();
        foreach ($items as $item) {
            $item->student_name  = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''));
            $item->guardian_name = $this->guardianName($item->guardian_first_name, $item->guardian_last_name);
            StudentStreamResolver::attachResolvedFields($item);
        }

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    /* ── student guardians (for the payment capture guardian picker) ───────── */

    public function studentGuardians(int $studentId)
    {
        $guardians = DB::table('guardians')
            ->where('student_id', $studentId)
            ->orderByDesc('is_primary_contact')
            ->get(['id', 'first_name', 'last_name', 'phone', 'email', 'relationship', 'is_primary_contact']);

        foreach ($guardians as $g) {
            $g->name = $this->guardianName($g->first_name, $g->last_name);
        }

        return response()->json($guardians);
    }

    /* ── store (record payment + allocate) ────────────────────────────────── */

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'                  => 'required|exists:students,id',
            'academic_year_id'            => 'required|exists:academic_years,id',
            'term_id'                     => 'required|exists:terms,id',
            'amount'                      => 'required|numeric|min:0.01',
            'payment_method'              => 'required|in:cash,ecocash,bank_transfer,swipe,online,other',
            'reference_number'            => 'nullable|string|max:100',
            'guardian_id'                 => 'nullable|integer|exists:guardians,id',
            'payer_name'                  => 'nullable|string|max:150',
            'payer_phone'                 => 'nullable|string|max:20',
            'payment_date'                => 'required|date',
            'notes'                       => 'nullable|string',
            // §2.3: bursar may direct the payment to specific bills/categories instead of the
            // automatic oldest-unpaid-first default. Omit entirely to keep the old behavior.
            'allocations'                 => 'nullable|array|min:1',
            'allocations.*.student_bill_id' => 'required_with:allocations|integer',
            'allocations.*.amount'          => 'required_with:allocations|numeric|min:0.01',
        ]);

        if (!empty($data['guardian_id'])) {
            $belongsToStudent = DB::table('guardians')
                ->where('id', $data['guardian_id'])
                ->where('student_id', $data['student_id'])
                ->exists();
            if (!$belongsToStudent) {
                return response()->json(['message' => 'Selected guardian does not belong to this student.'], 422);
            }
        }

        // Overpayment is allowed by design: any amount beyond what bills can absorb
        // becomes a credit on the account (a negative ledger balance) that automatically
        // nets against whatever gets billed next — see FeeAccountService::openingBalance.

        // Validate manual allocations, if given, against real bills before touching anything.
        // The allocated total may be *less* than the payment amount — the remainder
        // becomes account credit rather than being forced onto a specific bill.
        $manualBills = null;
        if (!empty($data['allocations'])) {
            $sum = round(array_sum(array_column($data['allocations'], 'amount')), 2);
            if ($sum > round((float) $data['amount'], 2) + 0.01) {
                return response()->json(['message' => 'Allocated amounts cannot exceed the total payment amount.'], 422);
            }
            $manualBills = [];
            foreach ($data['allocations'] as $row) {
                $bill = DB::table('student_bills')
                    ->where('id', $row['student_bill_id'])
                    ->where('student_id', $data['student_id'])
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->first();
                if (!$bill) {
                    return response()->json(['message' => "Bill #{$row['student_bill_id']} is not a valid outstanding bill for this student."], 422);
                }
                if ($row['amount'] > (float) $bill->balance + 0.01) {
                    return response()->json(['message' => "Allocation of \${$row['amount']} exceeds bill {$bill->bill_number}'s balance of \${$bill->balance}."], 422);
                }
                $manualBills[] = ['bill' => $bill, 'amount' => (float) $row['amount']];
            }
        }

        DB::beginTransaction();
        try {
            $receiptNumber = $this->nextReceiptNumber();
            $paymentReference = PaymentReferenceGenerator::generateReference();

            $paymentId = DB::table('finance_payments')->insertGetId([
                'receipt_number'    => $receiptNumber,
                'payment_reference' => $paymentReference,
                'student_id'       => $data['student_id'],
                'guardian_id'      => $data['guardian_id'] ?? null,
                'academic_year_id' => $data['academic_year_id'],
                'term_id'          => $data['term_id'],
                'amount'           => $data['amount'],
                'payment_method'   => $data['payment_method'],
                'reference_number' => $data['reference_number'] ?? null,
                'payer_name'       => $data['payer_name'] ?? null,
                'payer_phone'      => $data['payer_phone'] ?? null,
                'received_by'      => Auth::id(),
                'payment_date'     => $data['payment_date'],
                'notes'            => $data['notes'] ?? null,
                'status'           => 'active',
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            if ($manualBills !== null) {
                // Bursar-directed allocation: apply exactly what was specified.
                foreach ($manualBills as $entry) {
                    $this->applyAllocation($paymentId, $entry['bill'], $entry['amount']);
                }
            } else {
                // Default: oldest-due bill first.
                $bills = DB::table('student_bills')
                    ->where('student_id', $data['student_id'])
                    ->whereIn('status', ['unpaid', 'partial'])
                    ->orderBy('due_date')
                    ->orderBy('created_at')
                    ->get();

                $remaining = (float) $data['amount'];
                foreach ($bills as $bill) {
                    if ($remaining <= 0) break;
                    $allocated = min($remaining, (float) $bill->balance);
                    $this->applyAllocation($paymentId, $bill, $allocated);
                    $remaining -= $allocated;
                }
            }

            // Audit trail — the full payment amount reduces the running balance, regardless
            // of how much of it landed on a specific bill; any excess sits as account credit.
            $balanceAfter = FeeAccountService::currentLedgerBalance($data['student_id']) - (float) $data['amount'];

            DB::table('student_account_transactions')->insert([
                'student_id'       => $data['student_id'],
                'academic_year_id' => $data['academic_year_id'],
                'term_id'          => $data['term_id'],
                'transaction_type' => 'payment',
                'reference_type'   => 'finance_payments',
                'reference_id'     => $paymentId,
                'description'      => "Payment received - {$receiptNumber}",
                'debit'            => 0,
                'credit'           => $data['amount'],
                'balance_after'    => $balanceAfter,
                'created_by'       => Auth::id(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::commit();

            return response()->json([
                'message'           => 'Payment recorded successfully.',
                'receipt_number'    => $receiptNumber,
                'payment_reference' => $paymentReference,
                'payment_id'        => $paymentId,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /** Apply one allocation of $amount from a payment onto a bill — bill update + ledger row. */
    private function applyAllocation(int $paymentId, object $bill, float $amount): void
    {
        DB::table('payment_allocations')->insert([
            'payment_id'       => $paymentId,
            'student_bill_id'  => $bill->id,
            'amount_allocated' => $amount,
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        $newAmountPaid = (float) $bill->amount_paid + $amount;
        $newBalance    = (float) $bill->amount - $newAmountPaid;
        $newStatus     = $newBalance <= 0 ? 'paid' : 'partial';

        DB::table('student_bills')->where('id', $bill->id)->update([
            'amount_paid' => $newAmountPaid,
            'balance'     => max(0, $newBalance),
            'status'      => $newStatus,
            'updated_at'  => now(),
        ]);
    }

    /* ── receipt ──────────────────────────────────────────────────────────── */

    public function receipt(int $id)
    {
        $payment = DB::table('finance_payments as fp')
            ->join('students', 'fp.student_id', '=', 'students.id')
            ->join('academic_years', 'fp.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'fp.term_id', '=', 'terms.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->leftJoin('guardians', 'fp.guardian_id', '=', 'guardians.id')
            ->select(
                'fp.*',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.admission_number',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'users.name as received_by_name',
                'guardians.first_name as guardian_first_name',
                'guardians.last_name as guardian_last_name',
                'guardians.phone as guardian_phone'
            )
            ->where('fp.id', $id)->first();

        abort_if(!$payment, 404, 'Payment not found.');
        $payment->student_name  = trim(($payment->first_name ?? '') . ' ' . ($payment->last_name ?? ''));
        $payment->guardian_name = $this->guardianName($payment->guardian_first_name, $payment->guardian_last_name);
        StudentStreamResolver::attachResolvedFields($payment);

        $allocations = DB::table('payment_allocations as pa')
            ->join('student_bills as sb', 'pa.student_bill_id', '=', 'sb.id')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->select('pa.amount_allocated', 'sb.bill_number', 'sb.description', 'fee_categories.name as category_name', 'fee_categories.code as category_code')
            ->where('pa.payment_id', $id)->get();

        $remainingBalance = FeeAccountService::outstandingBalance($payment->student_id);

        // §2.5: "Previous Balance" — the outstanding total immediately before this payment,
        // read from that payment's own ledger entry so it stays exact regardless of what
        // has happened to the account since (later payments, reversals, new bills…).
        $paymentLedgerRow = DB::table('student_account_transactions')
            ->where('reference_type', 'finance_payments')
            ->where('reference_id', $id)
            ->where('transaction_type', 'payment')
            ->first();
        $previousBalance = $paymentLedgerRow ? ((float) $paymentLedgerRow->balance_after + (float) $payment->amount) : null;

        return response()->json([
            ...(array) $payment,
            'allocations'       => $allocations,
            'remaining_balance' => $remainingBalance,
            'previous_balance'  => $previousBalance,
        ]);
    }

    /* ── reverse (§2.5 control statement: no hard delete, only traceable adjustments) ── */

    public function reverse(Request $request, int $id)
    {
        $data = $request->validate(['reason' => 'required|string|max:1000']);

        $payment = DB::table('finance_payments')->where('id', $id)->first();
        abort_if(!$payment, 404, 'Payment not found.');
        if ($payment->status === 'reversed') {
            return response()->json(['message' => 'This payment has already been reversed.'], 422);
        }

        DB::beginTransaction();
        try {
            $allocations = DB::table('payment_allocations')->where('payment_id', $id)->get();

            foreach ($allocations as $alloc) {
                $bill = DB::table('student_bills')->where('id', $alloc->student_bill_id)->first();
                if (!$bill) continue;

                $newAmountPaid = max(0, (float) $bill->amount_paid - (float) $alloc->amount_allocated);
                $newBalance    = (float) $bill->amount - $newAmountPaid;
                $newStatus     = $newBalance <= 0 ? 'paid' : ($newAmountPaid > 0 ? 'partial' : 'unpaid');

                DB::table('student_bills')->where('id', $bill->id)->update([
                    'amount_paid' => $newAmountPaid,
                    'balance'     => max(0, $newBalance),
                    'status'      => $bill->status === 'cancelled' ? 'cancelled' : $newStatus,
                    'updated_at'  => now(),
                ]);
            }

            // Undo the payment's effect on the running balance — the exact inverse of store().
            $balanceAfter = FeeAccountService::currentLedgerBalance($payment->student_id) + (float) $payment->amount;

            DB::table('student_account_transactions')->insert([
                'student_id'       => $payment->student_id,
                'academic_year_id' => $payment->academic_year_id,
                'term_id'          => $payment->term_id,
                'transaction_type' => 'adjustment',
                'reference_type'   => 'finance_payments',
                'reference_id'     => $payment->id,
                'description'      => "Payment reversed - {$payment->receipt_number}",
                'debit'            => $payment->amount,
                'credit'           => 0,
                'balance_after'    => $balanceAfter,
                'created_by'       => Auth::id(),
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            DB::table('finance_payments')->where('id', $id)->update(['status' => 'reversed', 'updated_at' => now()]);

            DB::table('payment_adjustments')->insert([
                'finance_payment_id' => $id,
                'type'                => 'reversal',
                'reason'              => $data['reason'],
                'adjusted_by'         => Auth::id(),
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Payment reversed.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /* ── student payments ─────────────────────────────────────────────────── */

    public function studentPayments(int $studentId)
    {
        $payments = DB::table('finance_payments as fp')
            ->join('terms', 'fp.term_id', '=', 'terms.id')
            ->join('academic_years', 'fp.academic_year_id', '=', 'academic_years.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->select('fp.*', 'terms.name as term_name', 'academic_years.name as academic_year_name', 'users.name as received_by_name')
            ->where('fp.student_id', $studentId)
            ->orderByDesc('fp.payment_date')
            ->get();

        return response()->json($payments);
    }
}
