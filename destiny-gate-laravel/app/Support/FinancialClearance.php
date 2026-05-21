<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class FinancialClearance
{
    public static function currentPeriod(): ?object
    {
        return DB::table('terms as t')
            ->join('academic_years as ay', 't.academic_year_id', '=', 'ay.id')
            ->where('t.is_current', true)
            ->select('ay.id as academic_year_id', 't.id as term_id', 'ay.name as academic_year_name', 't.name as term_name')
            ->first()
            ?: DB::table('terms as t')
                ->join('academic_years as ay', 't.academic_year_id', '=', 'ay.id')
                ->where('ay.is_active', true)
                ->orderByDesc('t.start_date')
                ->select('ay.id as academic_year_id', 't.id as term_id', 'ay.name as academic_year_name', 't.name as term_name')
                ->first();
    }

    public static function summary(int $studentId, ?int $academicYearId = null, ?int $termId = null): array
    {
        if (!$academicYearId || !$termId) {
            $current = self::currentPeriod();
            $academicYearId ??= $current?->academic_year_id;
            $termId ??= $current?->term_id;
        }

        if (!$academicYearId || !$termId) {
            return [
                'academic_year_id' => null,
                'term_id' => null,
                'required_balance' => 0.0,
                'amount_paid' => 0.0,
                'outstanding_balance' => 0.0,
                'status' => 'cleared',
                'message' => null,
            ];
        }

        $required = (float) DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', '!=', 'cancelled')
            ->sum('amount');

        $balance = (float) DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', '!=', 'cancelled')
            ->sum('balance');

        $paidFromBills = (float) DB::table('student_bills')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->where('status', '!=', 'cancelled')
            ->sum('amount_paid');

        $paidFromPayments = (float) DB::table('finance_payments')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('term_id', $termId)
            ->sum('amount');

        $paidFromAllocations = (float) DB::table('payment_allocations as pa')
            ->join('student_bills as sb', 'pa.student_bill_id', '=', 'sb.id')
            ->where('sb.student_id', $studentId)
            ->where('sb.academic_year_id', $academicYearId)
            ->where('sb.term_id', $termId)
            ->where('sb.status', '!=', 'cancelled')
            ->sum('pa.amount_allocated');

        $paid = max($paidFromBills, $paidFromPayments, $paidFromAllocations);

        return [
            'academic_year_id' => (int) $academicYearId,
            'term_id' => (int) $termId,
            'required_balance' => round($required, 2),
            'amount_paid' => round($paid, 2),
            'outstanding_balance' => round($balance, 2),
            'status' => $balance <= 0 ? 'cleared' : 'pending',
            'message' => $balance <= 0 ? null : 'Your results are currently unavailable because your school fees balance for this term has not been fully cleared. Please contact the bursar\'s office.',
            'parent_message' => $balance <= 0 ? null : 'Results for this student are currently withheld pending fee clearance.',
        ];
    }

    public static function isCleared(int $studentId, ?int $academicYearId = null, ?int $termId = null): bool
    {
        return self::summary($studentId, $academicYearId, $termId)['status'] === 'cleared';
    }
}
