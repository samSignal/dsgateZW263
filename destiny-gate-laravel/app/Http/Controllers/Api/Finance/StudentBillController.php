<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class StudentBillController extends Controller
{
    /* ── helpers ─────────────────────────────────────────────────────────── */

    private function nextBillNumber(): string
    {
        $year = date('Y');
        $last = DB::table('student_bills')
            ->where('bill_number', 'like', "BILL-{$year}-%")
            ->orderByDesc('id')->value('bill_number');
        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return "BILL-{$year}-" . str_pad($seq, 5, '0', STR_PAD_LEFT);
    }

    private function createBill(int $studentId, int $yearId, int $termId, int $catId, string $desc, float $amount, ?string $dueDate, ?int $fsId): ?int
    {
        // Prevent duplicate bill for same student + fee_structure
        if ($fsId) {
            $exists = DB::table('student_bills')
                ->where('student_id', $studentId)
                ->where('fee_structure_id', $fsId)
                ->where('status', '!=', 'cancelled')
                ->exists();
            if ($exists) return null;
        }

        $billId = DB::table('student_bills')->insertGetId([
            'bill_number'      => $this->nextBillNumber(),
            'student_id'       => $studentId,
            'academic_year_id' => $yearId,
            'term_id'          => $termId,
            'fee_structure_id' => $fsId,
            'fee_category_id'  => $catId,
            'description'      => $desc,
            'amount'           => $amount,
            'amount_paid'      => 0,
            'balance'          => $amount,
            'status'           => 'unpaid',
            'due_date'         => $dueDate,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        // Audit trail
        $runningBalance = DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->sum('balance');

        DB::table('student_account_transactions')->insert([
            'student_id'       => $studentId,
            'academic_year_id' => $yearId,
            'term_id'          => $termId,
            'transaction_type' => 'bill',
            'reference_type'   => 'student_bills',
            'reference_id'     => $billId,
            'description'      => $desc,
            'debit'            => $amount,
            'credit'           => 0,
            'balance_after'    => $runningBalance,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return $billId;
    }

    /* ── index ────────────────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $q = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('academic_years', 'sb.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->leftJoin('classes', 'students.class_id', '=', 'classes.id')
            ->select(
                'sb.*',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'students.admission_number',
                'fee_categories.name as category_name',
                'academic_years.name as academic_year_name',
                'terms.name as term_name',
                'classes.class_name',
                'classes.stream'
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
                  ->orWhere('sb.bill_number', 'like', $s);
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

    public function show(int $id)
    {
        $bill = DB::table('student_bills as sb')
            ->join('students', 'sb.student_id', '=', 'students.id')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('academic_years', 'sb.academic_year_id', '=', 'academic_years.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->select('sb.*',
                DB::raw("CONCAT(students.first_name,' ',students.last_name) as student_name"),
                'students.student_number',
                'fee_categories.name as category_name',
                'academic_years.name as academic_year_name',
                'terms.name as term_name'
            )
            ->where('sb.id', $id)->first();
        abort_if(!$bill, 404, 'Bill not found.');

        $allocations = DB::table('payment_allocations as pa')
            ->join('finance_payments as fp', 'pa.payment_id', '=', 'fp.id')
            ->select('pa.*', 'fp.receipt_number', 'fp.payment_date', 'fp.payment_method')
            ->where('pa.student_bill_id', $id)->get();

        return response()->json([...(array)$bill, 'allocations' => $allocations]);
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

        // Get active fee structures for this form/year/term
        $structures = DB::table('finance_fee_structures')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where('form_id', $data['form_id'])
            ->where('is_active', true)
            ->get();

        if ($structures->isEmpty()) {
            return response()->json(['message' => 'No active fee structures found for this form/term.'], 422);
        }

        // Get active students in this form's classes
        $students = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->where('classes.form_id', $data['form_id'])
            ->where('students.status', 'active')
            ->pluck('students.id');

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

        $structures = DB::table('finance_fee_structures')
            ->where('academic_year_id', $data['academic_year_id'])
            ->where('term_id', $data['term_id'])
            ->where(function ($q) use ($stream, $data) {
                $q->where('stream_id', $data['stream_id'])
                  ->orWhere(function ($q2) use ($stream) {
                      $q2->where('form_id', $stream->form_id)->whereNull('stream_id');
                  });
            })
            ->where('is_active', true)
            ->get();

        if ($structures->isEmpty()) {
            return response()->json(['message' => 'No active fee structures found for this stream/term.'], 422);
        }

        // Get class linked to this stream
        $students = DB::table('students')
            ->join('classes', 'students.class_id', '=', 'classes.id')
            ->where('classes.stream_id', $data['stream_id'])
            ->where('students.status', 'active')
            ->pluck('students.id');

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
            'balance_after'    => 0,
            'created_by'       => Auth::id(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return response()->json(['message' => 'Bill cancelled.']);
    }

    /* ── student outstanding balance ──────────────────────────────────────── */

    public function studentBalance(int $studentId)
    {
        $balance = DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('status', '!=', 'cancelled')
            ->sum('balance');

        $bills = DB::table('student_bills as sb')
            ->join('fee_categories', 'sb.fee_category_id', '=', 'fee_categories.id')
            ->join('terms', 'sb.term_id', '=', 'terms.id')
            ->select('sb.*', 'fee_categories.name as category_name', 'terms.name as term_name')
            ->where('sb.student_id', $studentId)
            ->where('sb.status', '!=', 'cancelled')
            ->orderBy('sb.due_date')
            ->get();

        return response()->json(['balance' => $balance, 'bills' => $bills]);
    }
}
