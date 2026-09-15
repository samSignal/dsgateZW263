<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Support\BillGenerationService;
use App\Support\FeeAccountService;
use App\Support\StudentStreamResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentBillController extends Controller
{
    /* ── helpers ─────────────────────────────────────────────────────────── */

    private function createBill(int $studentId, int $yearId, int $termId, int $catId, string $desc, float $amount, ?string $dueDate, ?int $fsId): ?int
    {
        return BillGenerationService::createBill($studentId, $yearId, $termId, $catId, $desc, $amount, $dueDate, $fsId);
    }

    /* ── index ────────────────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $q = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('academic_years', 'sb.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->select(
                'sb.*',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.admission_number',
                'fee_categories.name as category_name',
                'academic_years.name as academic_year_name',
                'terms.name as term_name'
            )
            ->orderByDesc('sb.created_at');

        if ($request->student_id)       $q->where('sb.student_id', $request->student_id);
        if ($request->academic_year_id) $q->where('sb.academic_year_id', $request->academic_year_id);
        if ($request->term_id)          $q->where('sb.term_id', $request->term_id);
        if ($request->status)           $q->where('sb.status', $request->status);
        if ($request->search) {
            $s = '%' . $request->search . '%';
            $q->where(function ($x) use ($s) {
                $x->where('students.first_name', 'like', $s)
                  ->orWhere('students.last_name', 'like', $s)
                  ->orWhere('students.student_number', 'like', $s)
                  ->orWhere('students.admission_number', 'like', $s)
                  ->orWhere('sb.bill_number', 'like', $s);
            });
        }

        $perPage = (int)($request->per_page ?? 20);
        $page    = (int)($request->page ?? 1);
        $total   = (clone $q)->count();
        $items   = $q->offset(($page - 1) * $perPage)->limit($perPage)->get();
        foreach ($items as $item) {
            $item->student_name = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''));
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

    public function show(int $id)
    {
        $bill = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('academic_years', 'sb.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->select('sb.*',
                'students.first_name',
                'students.last_name',
                'students.student_number',
                'students.admission_number',
                'fee_categories.name as category_name',
                'academic_years.name as academic_year_name',
                'terms.name as term_name'
            )
            ->where('sb.id', $id)->first();
        abort_if(!$bill, 404, 'Bill not found.');
        $bill->student_name = trim(($bill->first_name ?? '') . ' ' . ($bill->last_name ?? ''));
        StudentStreamResolver::attachResolvedFields($bill);

        $allocations = DB::table('payment_allocations as pa')
            ->join('finance_payments as fp', 'pa.payment_id', '=', 'fp.id')
            ->select('pa.*', 'fp.receipt_number', 'fp.payment_date', 'fp.payment_method')
            ->where('pa.student_bill_id', $id)->get();

        return response()->json([...(array)$bill, 'allocations' => $allocations]);
    }

    /* ── billing status (billed vs not-yet-billed) for the Generate Bills screen ─── */

    public function billingStatus(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'form_id'          => 'nullable|exists:forms,id',
            'stream_id'        => 'nullable|exists:streams,id',
        ]);

        if (!empty($data['stream_id'])) {
            $studentIds = StudentStreamResolver::studentIdsForStream((int) $data['stream_id'], (int) $data['academic_year_id']);
        } elseif (!empty($data['form_id'])) {
            $studentIds = StudentStreamResolver::studentIdsForForm((int) $data['form_id'], (int) $data['academic_year_id']);
        } else {
            $studentIds = DB::table('students')->where('status', 'active')->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        if (empty($studentIds)) {
            return response()->json([]);
        }

        $students = DB::table('students')
            ->whereIn('id', $studentIds)
            ->select('id', 'first_name', 'last_name', 'student_number', 'admission_number')
            ->orderBy('first_name')
            ->get();
        $students = collect(StudentStreamResolver::attachResolvedFieldsToCollection($students));

        $bills = DB::table('student_bills')
            ->whereIn('student_id', $studentIds)
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('status', '!=', 'cancelled')
            ->select(
                'student_id',
                DB::raw('SUM(amount) as total_billed'),
                DB::raw('SUM(amount_paid) as total_paid'),
                DB::raw('SUM(balance) as total_balance')
            )
            ->groupBy('student_id')
            ->get()
            ->keyBy('student_id');

        $result = $students->map(function ($s) use ($bills) {
            $b = $bills->get($s->id);
            $isBilled = (bool) $b;
            $balance = $b ? (float) $b->total_balance : 0.0;
            $status = !$isBilled ? 'unbilled' : ($balance <= 0 ? 'paid' : ((float) $b->total_paid > 0 ? 'partial' : 'unpaid'));

            return [
                'id'                => $s->id,
                'name'              => trim(($s->first_name ?? '') . ' ' . ($s->last_name ?? '')),
                'student_number'    => $s->student_number,
                'admission_number'  => $s->admission_number,
                'class_name'        => $s->resolved_stream_name ?? null,
                'is_billed'         => $isBilled,
                'total_billed'      => $b ? round((float) $b->total_billed, 2) : 0.0,
                'total_paid'        => $b ? round((float) $b->total_paid, 2) : 0.0,
                'balance'           => round($balance, 2),
                'status'            => $status,
            ];
        })->values();

        return response()->json($result);
    }

    /* ── generate for one student ─────────────────────────────────────────── */

    public function generateForStudent(Request $request)
    {
        $data = $request->validate([
            'student_id'       => 'required|exists:students,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
            'fee_structure_ids'=> 'required|array|min:1',
            'fee_structure_ids.*' => 'exists:finance_fee_structures,id',
        ]);

        $student = DB::table('students')->find($data['student_id']);
        if ($student->status !== 'active') {
            return response()->json(['message' => 'Only active students can be billed.'], 422);
        }

        DB::beginTransaction();
        try {
            $created = 0;
            foreach ($data['fee_structure_ids'] as $fsId) {
                $fs = DB::table('finance_fee_structures')->find($fsId);
                if (!$fs || !$fs->is_active) continue;
                $billId = $this->createBill($data['student_id'], $data['academic_year_id'], $data['term_id'], $fs->fee_category_id, $fs->name, $fs->amount, $fs->due_date, $fsId);
                if ($billId) $created++;
            }
            DB::commit();
            return response()->json(['message' => "{$created} bill(s) generated.", 'created' => $created]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /* ── generate for form ────────────────────────────────────────────────── */

    public function generateForForm(Request $request)
    {
        $data = $request->validate([
            'form_id'          => 'required|exists:forms,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
        ]);

        // Active fee structures for this form/year/term — includes school-wide structures
        // (form_id null) alongside ones scoped to this exact form, matching how
        // BillGenerationService::autoGenerateForStudent() bills a newly enrolled student.
        $structures = DB::table('finance_fee_structures')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where(function ($q) use ($data) {
                $q->whereNull('form_id')->orWhere('form_id', $data['form_id']);
            })
            ->where('is_active', true)
            ->get();

        if ($structures->isEmpty()) {
            return response()->json(['message' => 'No active fee structures found for this form/term.'], 422);
        }

        $students = collect(StudentStreamResolver::studentIdsForForm((int) $data['form_id'], (int) $data['academic_year_id']));

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No active students found in this form.'], 422);
        }

        DB::beginTransaction();
        try {
            $created = 0;
            foreach ($students as $studentId) {
                foreach ($structures as $fs) {
                    $billId = $this->createBill($studentId, $data['academic_year_id'], $data['term_id'], $fs->fee_category_id, $fs->name, $fs->amount, $fs->due_date, $fs->id);
                    if ($billId) $created++;
                }
            }
            DB::commit();
            return response()->json(['message' => "{$created} bill(s) generated for " . count($students) . " students.", 'created' => $created]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /* ── generate for stream ──────────────────────────────────────────────── */

    public function generateForStream(Request $request)
    {
        $data = $request->validate([
            'stream_id'        => 'required|exists:streams,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'term_id'          => 'required|exists:terms,id',
        ]);

        $stream = DB::table('streams')->find($data['stream_id']);

        // Also includes school-wide structures (form_id AND stream_id both null), which the
        // stream-specific/form-specific branches below don't otherwise catch — see the same
        // fix in generateForForm() above.
        $structures = DB::table('finance_fee_structures')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where(function ($q) use ($stream, $data) {
                $q->where('stream_id', $data['stream_id'])
                  ->orWhere(function ($q2) use ($stream) {
                      $q2->where('form_id', $stream->form_id)->whereNull('stream_id');
                  })
                  ->orWhere(function ($q3) {
                      $q3->whereNull('form_id')->whereNull('stream_id');
                  });
            })
            ->where('is_active', true)
            ->get();

        if ($structures->isEmpty()) {
            return response()->json(['message' => 'No active fee structures found for this stream/term.'], 422);
        }

        $students = collect(StudentStreamResolver::studentIdsForStream((int) $data['stream_id'], (int) $data['academic_year_id']));

        if ($students->isEmpty()) {
            return response()->json(['message' => 'No active students found in this stream.'], 422);
        }

        DB::beginTransaction();
        try {
            $created = 0;
            foreach ($students as $studentId) {
                foreach ($structures as $fs) {
                    $billId = $this->createBill($studentId, $data['academic_year_id'], $data['term_id'], $fs->fee_category_id, $fs->name, $fs->amount, $fs->due_date, $fs->id);
                    if ($billId) $created++;
                }
            }
            DB::commit();
            return response()->json(['message' => "{$created} bill(s) generated.", 'created' => $created]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed: ' . $e->getMessage()], 500);
        }
    }

    /* ── cancel bill ──────────────────────────────────────────────────────── */

    public function cancelBill(int $id)
    {
        $bill = DB::table('student_bills')->find($id);
        abort_if(!$bill, 404, 'Bill not found.');

        if ($bill->amount_paid > 0) {
            return response()->json(['message' => 'Cannot cancel a bill that has payments.'], 422);
        }

        DB::table('student_bills')->where('id', $id)->update([
            'status'     => 'cancelled',
            'updated_at' => now(),
        ]);

        DB::table('student_account_transactions')->insert([
            'student_id'       => $bill->student_id,
            'academic_year_id' => $bill->academic_year_id,
            'term_id'          => $bill->term_id,
            'transaction_type' => 'cancellation',
            'reference_type'   => 'student_bills',
            'reference_id'     => $id,
            'description'      => 'Bill cancelled: ' . $bill->description,
            'debit'            => 0,
            'credit'           => $bill->amount,
            'balance_after'    => FeeAccountService::currentLedgerBalance($bill->student_id) - $bill->amount,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['message' => 'Bill cancelled.']);
    }

    /* ── student outstanding balance ──────────────────────────────────────── */

    public function studentBalance(int $studentId, Request $request)
    {
        $balance = FeeAccountService::outstandingBalance($studentId);

        $bills = DB::table('student_bills as sb')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->select('sb.*', 'fee_categories.name as category_name', 'fee_categories.code as category_code', 'terms.name as term_name')
            ->where('sb.student_id', $studentId)
            ->where('sb.status', '!=', 'cancelled')
            ->orderBy('sb.due_date')
            ->get();

        $response = ['balance' => $balance, 'bills' => $bills, 'is_billed' => $bills->isNotEmpty()];

        // §2.3 Opening Balance breakdown — only computable once a term is given (defaults to the current term if not).
        $termId = $request->term_id ?: DB::table('terms')->where('is_current', true)->value('id');
        $yearId = $request->academic_year_id ?: DB::table('terms')->where('id', $termId)->value('academic_year_id');
        if ($termId && $yearId) {
            $response['account_summary'] = FeeAccountService::accountSummary($studentId, (int) $yearId, (int) $termId);
        }

        return response()->json($response);
    }
}
