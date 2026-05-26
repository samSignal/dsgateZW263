<?php

namespace App\Support\Admissions;

use Illuminate\Support\Facades\DB;

class AdmissionIntakeService
{
    public static function isIntakeOpen(int $academicYearId): bool
    {
        $row = DB::table('admission_intakes')->where('academic_year_id', $academicYearId)->where('is_active', true)->first();
        if (!$row) {
            return !config('admissions.intake.require_open_intake_for_drafts', true) ? true : true;
        }

        $now = now();
        if ($row->opens_at && $now->lt($row->opens_at)) return false;
        if ($row->closes_at && $now->gte($row->closes_at)) return false;
        return true;
    }

    public static function intakeCloseAt(int $academicYearId): ?string
    {
        $row = DB::table('admission_intakes')->where('academic_year_id', $academicYearId)->where('is_active', true)->first();
        if (!$row) return null;
        return $row->closes_at ? (string) $row->closes_at : null;
    }
}

