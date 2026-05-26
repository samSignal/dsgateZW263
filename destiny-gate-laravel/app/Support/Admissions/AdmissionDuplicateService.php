<?php

namespace App\Support\Admissions;

use Illuminate\Support\Facades\DB;

class AdmissionDuplicateService
{
    public static function findSubmittedApplicationId(int $intakeAcademicYearId, string $birthCertNormalized): ?int
    {
        $id = DB::table('admission_application_submissions')
            ->where('intake_academic_year_id', $intakeAcademicYearId)
            ->where('birth_certificate_number_normalized', $birthCertNormalized)
            ->value('application_id');

        return $id ? (int) $id : null;
    }

    public static function findActiveDraftId(int $intakeAcademicYearId, string $birthCertNormalized): ?int
    {
        $q = DB::table('admission_applications')
            ->where('academic_year_id', $intakeAcademicYearId)
            ->where('birth_certificate_number_normalized', $birthCertNormalized)
            ->whereNull('archived_at');

        $id = $q->orderByDesc('updated_at')->value('id');
        return $id ? (int) $id : null;
    }
}

