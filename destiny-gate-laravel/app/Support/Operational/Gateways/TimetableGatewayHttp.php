<?php

namespace App\Support\Operational\Gateways;

use App\Contracts\Operational\TimetableGateway;
use App\Support\Integrations\ResilientExternalCall;
use Illuminate\Support\Facades\Http;

class TimetableGatewayHttp implements TimetableGateway
{
    public function activate(array $ctx, bool $dryRun): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        $academicYearId = (int) ($ctx['academic_year_id'] ?? 0);
        $termId = (int) ($ctx['term_id'] ?? 0);
        if ($studentId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'student_id is required.'];
        if ($academicYearId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'academic_year_id is required.'];
        if ($termId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'term_id is required.'];
        if ($dryRun) return ['ok' => true, 'code' => 'DRY_RUN', 'idempotent' => false, 'rollback_meta' => ['created' => false]];

        $cfg = (array) config('admissions.operational.integrations.timetable', []);
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $token = (string) ($cfg['token'] ?? '');
        $timeout = max(1, (int) ($cfg['timeout_seconds'] ?? 10));
        if ($baseUrl === '' || $token === '') return ['ok' => false, 'code' => 'CONFIG_MISSING', 'message' => 'Timetable integration is not configured.'];

        $idem = (string) ($ctx['step_idempotency_key'] ?? $ctx['idempotency_key'] ?? '');

        return ResilientExternalCall::call('timetable', 'operational.activate', $ctx, function () use ($baseUrl, $token, $timeout, $studentId, $academicYearId, $termId, $ctx, $idem) {
            $res = Http::timeout($timeout)
                ->withToken($token)
                ->withHeaders(array_filter([
                    'Idempotency-Key' => $idem !== '' ? $idem : null,
                    'X-Correlation-Id' => $ctx['correlation_id'] ?? null,
                ]))
                ->post($baseUrl . '/participants/activate', [
                    'student_id' => $studentId,
                    'academic_year_id' => $academicYearId,
                    'term_id' => $termId,
                ]);

            if (!$res->successful()) {
                $status = $res->status();
                if (in_array($status, [408, 504], true)) return ['ok' => false, 'code' => 'TIMEOUT', 'message' => 'Timetable timeout'];
                if ($status >= 500) return ['ok' => false, 'code' => 'EXTERNAL_5XX', 'message' => 'Timetable error'];
                return ['ok' => false, 'code' => 'EXTERNAL_4XX', 'message' => 'Timetable rejected request'];
            }

            $body = $res->json() ?: [];
            $externalRef = is_string($body['membership_id'] ?? null) ? (string) $body['membership_id'] : null;
            return [
                'ok' => true,
                'code' => 'OK',
                'idempotent' => !empty($body['idempotent']),
                'external_ref' => $externalRef,
                'rollback_meta' => [
                    'created' => empty($body['idempotent']),
                    'external_ref' => $externalRef,
                ],
            ];
        }, isset($ctx['application_id']) ? (int) $ctx['application_id'] : null, isset($ctx['initiated_by']) ? (int) $ctx['initiated_by'] : null, request());
    }

    public function rollback(array $ctx, array $rollbackMeta): array
    {
        $cfg = (array) config('admissions.operational.integrations.timetable', []);
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $token = (string) ($cfg['token'] ?? '');
        $timeout = max(1, (int) ($cfg['timeout_seconds'] ?? 10));
        if ($baseUrl === '' || $token === '') return ['ok' => false, 'code' => 'CONFIG_MISSING', 'message' => 'Timetable integration is not configured.'];

        $externalRef = is_string($rollbackMeta['external_ref'] ?? null) ? (string) $rollbackMeta['external_ref'] : null;
        if (!$externalRef) return ['ok' => true, 'code' => 'OK'];

        return ResilientExternalCall::call('timetable', 'operational.rollback', $ctx, function () use ($baseUrl, $token, $timeout, $externalRef, $ctx) {
            $res = Http::timeout($timeout)
                ->withToken($token)
                ->withHeaders(array_filter(['X-Correlation-Id' => $ctx['correlation_id'] ?? null]))
                ->post($baseUrl . '/participants/deactivate', [
                    'external_ref' => $externalRef,
                    'student_id' => $ctx['student_id'] ?? null,
                ]);

            if (!$res->successful()) {
                $status = $res->status();
                if (in_array($status, [408, 504], true)) return ['ok' => false, 'code' => 'TIMEOUT', 'message' => 'Timetable timeout'];
                if ($status >= 500) return ['ok' => false, 'code' => 'EXTERNAL_5XX', 'message' => 'Timetable error'];
                return ['ok' => false, 'code' => 'EXTERNAL_4XX', 'message' => 'Timetable rejected request'];
            }

            return ['ok' => true, 'code' => 'OK'];
        }, isset($ctx['application_id']) ? (int) $ctx['application_id'] : null, isset($ctx['initiated_by']) ? (int) $ctx['initiated_by'] : null, request());
    }

    public function reconcile(array $ctx): array
    {
        $cfg = (array) config('admissions.operational.integrations.timetable', []);
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $token = (string) ($cfg['token'] ?? '');
        $timeout = max(1, (int) ($cfg['timeout_seconds'] ?? 10));
        if ($baseUrl === '' || $token === '') return ['ok' => false, 'drift' => true, 'issues' => ['config_missing']];

        $studentId = (int) ($ctx['student_id'] ?? 0);
        if ($studentId <= 0) return ['ok' => false, 'drift' => true, 'issues' => ['missing_student_id']];

        $res = Http::timeout($timeout)
            ->withToken($token)
            ->withHeaders(array_filter(['X-Correlation-Id' => $ctx['correlation_id'] ?? null]))
            ->get($baseUrl . '/participants/' . $studentId);

        if (!$res->successful()) return ['ok' => false, 'drift' => true, 'issues' => ['timetable_unreachable']];

        $body = $res->json() ?: [];
        $active = !empty($body['active']);
        return ['ok' => $active, 'drift' => !$active, 'issues' => $active ? [] : ['timetable_membership_missing']];
    }

    public function health(): array
    {
        $cfg = (array) config('admissions.operational.integrations.timetable', []);
        $baseUrl = rtrim((string) ($cfg['base_url'] ?? ''), '/');
        $token = (string) ($cfg['token'] ?? '');
        if ($baseUrl === '' || $token === '') return ['ok' => false, 'adapter' => 'http', 'service' => 'timetable', 'code' => 'CONFIG_MISSING'];
        return ['ok' => true, 'adapter' => 'http', 'service' => 'timetable'];
    }
}

