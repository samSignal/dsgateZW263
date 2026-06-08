<?php

namespace App\Support\Operational\Gateways;

use App\Contracts\Operational\TimetableGateway;
use App\Operational\OperationalMarkerService;
use Illuminate\Support\Facades\DB;

class TimetableGatewayDb implements TimetableGateway
{
    public function activate(array $ctx, bool $dryRun): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        $academicYearId = (int) ($ctx['academic_year_id'] ?? 0);
        $termId = (int) ($ctx['term_id'] ?? 0);

        if ($studentId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'student_id is required.'];

        $hasEnrollment = DB::table('student_enrollments')
            ->where('student_id', $studentId)
            ->when($academicYearId > 0, fn ($q) => $q->where('academic_year_id', $academicYearId))
            ->when($termId > 0, fn ($q) => $q->where('term_id', $termId))
            ->exists();
        if (!$hasEnrollment) return ['ok' => false, 'code' => 'PREREQ_MISSING', 'message' => 'Student enrollment is required for timetable activation.'];

        $ref = 'TT-' . strtoupper(substr(hash('sha256', 'tt|' . $studentId . '|' . ($academicYearId ?: '0') . '|' . ($termId ?: '0')), 0, 12));
        return OperationalMarkerService::activate($studentId, 'timetable', $ref, [
            'academic_year_id' => $academicYearId ?: null,
            'term_id' => $termId ?: null,
            'idempotency_key' => $ctx['idempotency_key'] ?? null,
        ], $dryRun);
    }

    public function rollback(array $ctx, array $rollbackMeta): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::rollback($studentId, 'timetable', $rollbackMeta);
    }

    public function reconcile(array $ctx): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::reconcile($studentId, 'timetable');
    }

    public function health(): array
    {
        return ['ok' => true, 'adapter' => 'db_stub', 'service' => 'timetable'];
    }
}

