<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class StreamNativeEnrollmentEngine
{
    public static function ensureEnrollment(int $studentId, int $academicYearId, int $termId, ?int $updatedBy = null): void
    {
        $resolved = StudentStreamResolver::resolveByStudentId($studentId);
        if (!$resolved || !$resolved->stream_id || !$resolved->form_id) return;

        DB::table('student_enrollments')->updateOrInsert(
            [
                'student_id' => $studentId,
                'academic_year_id' => $academicYearId,
                'term_id' => $termId,
            ],
            [
                'form_id' => $resolved->form_id,
                'stream_id' => $resolved->stream_id,
                'category_id' => $resolved->category_id,
                'enrollment_status' => 'enrolled',
                'updated_by' => $updatedBy,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }

    public static function ensureForStream(int $academicYearId, int $termId, int $streamId, ?int $updatedBy = null): int
    {
        $studentIds = StudentStreamResolver::studentIdsForStream($streamId, $academicYearId);
        foreach ($studentIds as $studentId) {
            self::ensureEnrollment($studentId, $academicYearId, $termId, $updatedBy);
        }
        return count($studentIds);
    }
}

