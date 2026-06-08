<?php

namespace App\Support\Operational\Gateways;

use App\Contracts\Operational\ExaminationsGateway;
use App\Operational\OperationalMarkerService;
use Illuminate\Support\Facades\DB;

class ExaminationsGatewayDb implements ExaminationsGateway
{
    public function activate(array $ctx, bool $dryRun): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        $academicYearId = (int) ($ctx['academic_year_id'] ?? 0);
        if ($studentId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'student_id is required.'];
        if ($academicYearId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'academic_year_id is required.'];

        $financeOk = DB::table('finance_eligibility_markers')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('billing_ready', 1)
            ->exists();
        if (!$financeOk) return ['ok' => false, 'code' => 'FINANCE_HOLD', 'message' => 'Finance eligibility is required for examinations.'];

        $ref = 'EXM-' . strtoupper(substr(hash('sha256', 'exm|' . $studentId . '|' . $academicYearId), 0, 12));
        return OperationalMarkerService::activate($studentId, 'exams', $ref, [
            'academic_year_id' => $academicYearId,
            'idempotency_key' => $ctx['idempotency_key'] ?? null,
        ], $dryRun);
    }

    public function rollback(array $ctx, array $rollbackMeta): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::rollback($studentId, 'exams', $rollbackMeta);
    }

    public function reconcile(array $ctx): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::reconcile($studentId, 'exams');
    }

    public function health(): array
    {
        return ['ok' => true, 'adapter' => 'db_stub', 'service' => 'exams'];
    }
}

