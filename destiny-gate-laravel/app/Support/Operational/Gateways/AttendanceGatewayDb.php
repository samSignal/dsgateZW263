<?php

namespace App\Support\Operational\Gateways;

use App\Contracts\Operational\AttendanceGateway;
use App\Operational\OperationalMarkerService;
use Illuminate\Support\Facades\DB;

class AttendanceGatewayDb implements AttendanceGateway
{
    public function activate(array $ctx, bool $dryRun): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        if ($studentId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'student_id is required.'];

        $tt = DB::table('operational_service_markers')->where('student_id', $studentId)->where('service_code', 'timetable')->where('status', 'active')->exists();
        if (!$tt) return ['ok' => false, 'code' => 'PREREQ_MISSING', 'message' => 'Timetable activation is required for attendance.'];

        $ref = 'ATT-' . strtoupper(substr(hash('sha256', 'att|' . $studentId), 0, 12));
        return OperationalMarkerService::activate($studentId, 'attendance', $ref, [
            'idempotency_key' => $ctx['idempotency_key'] ?? null,
        ], $dryRun);
    }

    public function rollback(array $ctx, array $rollbackMeta): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::rollback($studentId, 'attendance', $rollbackMeta);
    }

    public function reconcile(array $ctx): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::reconcile($studentId, 'attendance');
    }

    public function health(): array
    {
        return ['ok' => true, 'adapter' => 'db_stub', 'service' => 'attendance'];
    }
}

