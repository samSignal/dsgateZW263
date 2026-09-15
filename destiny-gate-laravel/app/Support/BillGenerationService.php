<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Turns a Fee Structure "rule" into an actual charge (student_bills row) for one student.
 * Shared by every place that needs to bill a student: the manual Generate Bills screens
 * (by student / by form / by class) and the automatic billing triggered when a student is
 * created — see autoGenerateForStudent().
 */
class BillGenerationService
{
    public static function nextBillNumber(): string
    {
        $year = date('Y');
        $last = DB::table('student_bills')
            ->where('bill_number', 'like', "BILL-{$year}-%")
            ->orderByDesc('id')->value('bill_number');
        $seq = $last ? (int) substr($last, -5) + 1 : 1;
        return "BILL-{$year}-" . str_pad((string) $seq, 5, '0', STR_PAD_LEFT);
    }

    /** Creates one bill from one fee structure for one student. Returns null (no-op) if this
     *  student was already billed for this exact fee structure — safe to call repeatedly. */
    public static function createBill(int $studentId, int $yearId, int $termId, int $catId, string $desc, float $amount, ?string $dueDate, ?int $fsId): ?int
    {
        if ($fsId) {
            $exists = DB::table('student_bills')
                ->where('student_id', $studentId)
                ->where('fee_structure_id', $fsId)
                ->where('status', '!=', 'cancelled')
                ->exists();
            if ($exists) return null;
        }

        $billId = DB::table('student_bills')->insertGetId([
            'bill_number'      => self::nextBillNumber(),
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

        $runningBalance = FeeAccountService::currentLedgerBalance($studentId) + $amount;

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

    /**
     * Auto-bills a newly created/enrolled student against every active fee structure that
     * applies to them for the given (or current) term — the "what triggers billing for a
     * new student" answer, made automatic instead of requiring someone to remember to run
     * Generate Bills. Matches on the student's form via StudentStreamResolver (handles both
     * the modern stream_id linkage and the legacy class_id one), and includes structures
     * scoped to that exact form as well as ones that apply school-wide (form_id null).
     *
     * Deliberately swallows its own errors — a billing hiccup must never block student
     * creation/enrollment itself. Call sites should just fire-and-forget this.
     */
    public static function autoGenerateForStudent(int $studentId, ?int $academicYearId = null, ?int $termId = null): int
    {
        try {
            $term = $termId
                ? DB::table('terms')->where('id', $termId)->first()
                : DB::table('terms')->where('is_current', true)->first();
            if (!$term) return 0;

            $yearId = $academicYearId ?? $term->academic_year_id;

            $student = DB::table('students')->where('id', $studentId)->first();
            if (!$student || $student->status !== 'active') return 0;

            $resolved = StudentStreamResolver::resolveByStudentId($studentId);
            $formId = $resolved?->form_id ?? null;

            $structures = DB::table('finance_fee_structures')
                ->where('academic_year_id', $yearId)
                ->where('term_id', $term->id)
                ->where('is_active', true)
                ->where(function ($q) use ($formId) {
                    $q->whereNull('form_id');
                    if ($formId) $q->orWhere('form_id', $formId);
                })
                ->get();

            $created = 0;
            foreach ($structures as $fs) {
                $billId = self::createBill($studentId, $yearId, $term->id, $fs->fee_category_id, $fs->name, $fs->amount, $fs->due_date, $fs->id);
                if ($billId) $created++;
            }

            return $created;
        } catch (\Throwable $e) {
            report($e);
            return 0;
        }
    }
}
