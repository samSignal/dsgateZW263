<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    private function nextReceiptNumber(): string
    {
        $year = date('Y');
        $last = DB::table('finance_payments')
            ->where('receipt_number', 'like', "RCPT-{$year}-%")
            ->orderByDesc('id')->value('receipt_number');
        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return "RCPT-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    /* ── index ────────────────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $q = DB::table('finance_payments as fp')
            ->join('students', 'fp.student_id', '=', 'students.id')
            ->join('academic_years', 'fp.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'fp.term_id', '=', 'terms.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->select(
                'fp.*',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'users.name as received_by_name'
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
                  ->orWhere('fp.receipt_number', 'like', $s);
            });
        }

        $perPage = (int)($request->per_page ?? 20);
        $page    = (int)($request->page ?? 1);
        $total   = (clone $q)->count();
        $items   = $q->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return response()->json([
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ]);
    }

    /* ── store (record payment + allocate) ────────────────────────────────── */

    public function store(Request $request)
    {
        $data = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'amount'           => 'required|numeric|min:0.01',
            'payment_method'   => 'required|in:cash,ecocash,bank_transfer,swipe,online,other',
            'reference_number' => 'nullable|string|max:100',
            'payer_name'       => 'nullable|string|max:150',
            'payer_phone'      => 'nullable|string|max:20',
            'payment_date'     => 'required|date',
            'notes'            => 'nullable|string',
        ]);

        // Check outstanding balance
        $outstanding = DB::table('student_bills')
            ->where('student_id', $data['student_id'])
            ->whereIn('status', ['unpaid', 'partial'])
            ->sum('balance');

        if ($data['amount'] > $outstanding) {
            return response()->json([
                'message' => "Payment amount (\${$data['amount']}) cannot exceed outstanding balance (\${$outstanding}).",
            ], 422);
        }

        DB::beginTransaction();
        try {
            $receiptNumber = $this->nextReceiptNumber();

            $paymentId = DB::table('finance_payments')->insertGetId([
                'receipt_number'   => $receiptNumber,
                'student_id'       => $data['student_id'],
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
                'created_at'       => now(),
                'updated_at'       => now(),
            ]);

            // Allocate payment to bills (oldest due date first)
            $bills = DB::table('student_bills')
                ->where('student_id', $data['student_id'])
                ->whereIn('status', ['unpaid', 'partial'])
                ->orderBy('due_date')
                ->orderBy('created_at')
                ->get();

            $remaining = (float) $data['amount'];

            foreach ($bills as $bill) {
                if ($remaining <= 0) break;

                $billBalance = (float) $bill->balance;
                $allocated   = min($remaining, $billBalance);

                DB::table('payment_allocations')->insert([
                    'payment_id'       => $paymentId,
                    'student_bill_id'  => $bill->id,
                    'amount_allocated' => $allocated,
                    'created_at'       => now(),
                    'updated_at'       => now(),
                ]);

                $newAmountPaid = (float) $bill->amount_paid + $allocated;
                $newBalance    = (float) $bill->amount - $newAmountPaid;
                $newStatus     = $newBalance <= 0 ? 'paid' : 'partial';

                DB::table('student_bills')->where('id', $bill->id)->update([
                    'amount_paid' => $newAmountPaid,
                    'balance'     => max(0, $newBalance),
                    'status'      => $newStatus,
                    'updated_at'  => now(),
                ]);

                $remaining -= $allocated;
            }

            // Audit trail
            $balanceAfter = DB::table('student_bills')
                ->where('student_id', $data['student_id'])
                ->where('status', '!=', 'cancelled')
                ->sum('balance');

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
                'message'        => 'Payment recorded successfully.',
                'receipt_number' => $receiptNumber,
                'payment_id'     => $paymentId,
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /* ── receipt ──────────────────────────────────────────────────────────── */

    public function receipt(int $id)
    {
        $payment = DB::table('finance_payments as fp')
            ->join('students', 'fp.student_id', '=', 'students.id')
            ->join('academic_years', 'fp.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'fp.term_id', '=', 'terms.id')
            ->join('users', 'fp.received_by', '=', 'users.id')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select(
                'fp.*',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'students.admission_number',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'users.name as received_by_name',
                'classes.class_name',
                'classes.stream'
            )
            ->where('fp.id', $id)->first();

        abort_if(!$payment, 404, 'Payment not found.');

        $allocations = DB::table('payment_allocations as pa')
            ->join('student_bills as sb', 'pa.student_bill_id', '=', 'sb.id')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->select('pa.amount_allocated', 'sb.bill_number', 'sb.description', 'fee_categories.name as category_name')
            ->where('pa.payment_id', $id)->get();

        $remainingBalance = DB::table('student_bills')
            ->where('student_id', $payment->student_id)
            ->where('status', '!=', 'cancelled')
            ->sum('balance');

        return response()->json([
            ...(array) $payment,
            'allocations'       => $allocations,
            'remaining_balance' => $remainingBalance,
        ]);
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
