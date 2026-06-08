<?php

namespace App\Support\Operational\Gateways;

use App\Contracts\Operational\TransportGateway;
use App\Operational\OperationalMarkerService;
use Illuminate\Support\Facades\DB;

class TransportGatewayDb implements TransportGateway
{
    public function activate(array $ctx, bool $dryRun): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        if ($studentId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'student_id is required.'];

        $idActive = DB::table('operational_service_markers')->where('student_id', $studentId)->where('service_code', 'identity')->where('status', 'active')->exists();
        if (!$idActive) return ['ok' => false, 'code' => 'PREREQ_MISSING', 'message' => 'Identity activation is required for transport eligibility.'];

        $ref = 'TRN-' . strtoupper(substr(hash('sha256', 'trn|' . $studentId), 0, 12));
        return OperationalMarkerService::activate($studentId, 'transport', $ref, [
            'idempotency_key' => $ctx['idempotency_key'] ?? null,
        ], $dryRun);
    }

    public function rollback(array $ctx, array $rollbackMeta): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::rollback($studentId, 'transport', $rollbackMeta);
    }

    public function reconcile(array $ctx): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        return OperationalMarkerService::reconcile($studentId, 'transport');
    }

    public function health(): array
    {
        return ['ok' => true, 'adapter' => 'db_stub', 'service' => 'transport'];
    }
}

