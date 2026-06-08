<?php

namespace App\Operational;

use Illuminate\Support\Facades\DB;

class OperationalMarkerService
{
    public static function activate(int $studentId, string $serviceCode, ?string $externalRef, array $meta, bool $dryRun): array
    {
        if ($studentId <= 0 || $serviceCode === '') {
            return ['ok' => false, 'code' => 'INVALID', 'message' => 'Missing identifiers.'];
        }

        return DB::transaction(function () use ($studentId, $serviceCode, $externalRef, $meta, $dryRun) {
            $row = DB::table('operational_service_markers')
                ->where('student_id', $studentId)
                ->where('service_code', $serviceCode)
                ->lockForUpdate()
                ->first();

            if ($row && (string) $row->status === 'active') {
                return [
                    'ok' => true,
                    'code' => 'OK',
                    'idempotent' => true,
                    'marker_id' => (int) $row->id,
                    'rollback_meta' => ['created' => false],
                ];
            }

            if ($dryRun) {
                return [
                    'ok' => true,
                    'code' => 'DRY_RUN',
                    'idempotent' => false,
                    'marker_id' => null,
                    'rollback_meta' => ['created' => false],
                ];
            }

            $now = now();
            if ($row) {
                DB::table('operational_service_markers')->where('id', (int) $row->id)->update([
                    'status' => 'active',
                    'external_ref' => $externalRef ?? $row->external_ref,
                    'activated_at' => $now,
                    'deactivated_at' => null,
                    'meta' => json_encode($meta),
                    'updated_at' => $now,
                ]);
                return [
                    'ok' => true,
                    'code' => 'OK',
                    'idempotent' => false,
                    'marker_id' => (int) $row->id,
                    'rollback_meta' => ['created' => false],
                ];
            }

            $id = (int) DB::table('operational_service_markers')->insertGetId([
                'student_id' => $studentId,
                'service_code' => $serviceCode,
                'status' => 'active',
                'external_ref' => $externalRef,
                'activated_at' => $now,
                'deactivated_at' => null,
                'meta' => json_encode($meta),
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            return [
                'ok' => true,
                'code' => 'OK',
                'idempotent' => false,
                'marker_id' => $id,
                'rollback_meta' => ['created' => true, 'marker_id' => $id],
            ];
        });
    }

    public static function rollback(int $studentId, string $serviceCode, array $rollbackMeta): array
    {
        $markerId = !empty($rollbackMeta['marker_id']) ? (int) $rollbackMeta['marker_id'] : null;
        return DB::transaction(function () use ($studentId, $serviceCode, $markerId) {
            $q = DB::table('operational_service_markers')->where('student_id', $studentId)->where('service_code', $serviceCode);
            if ($markerId) $q->where('id', $markerId);
            $row = $q->lockForUpdate()->first();
            if (!$row) return ['ok' => true, 'code' => 'OK'];

            if ((string) $row->status !== 'active') return ['ok' => true, 'code' => 'OK'];

            DB::table('operational_service_markers')->where('id', (int) $row->id)->update([
                'status' => 'inactive',
                'deactivated_at' => now(),
                'updated_at' => now(),
            ]);

            return ['ok' => true, 'code' => 'OK'];
        });
    }

    public static function reconcile(int $studentId, string $serviceCode): array
    {
        $row = DB::table('operational_service_markers')->where('student_id', $studentId)->where('service_code', $serviceCode)->first();
        if (!$row) return ['ok' => false, 'drift' => true, 'issues' => ['marker_missing']];
        if ((string) $row->status !== 'active') return ['ok' => false, 'drift' => true, 'issues' => ['marker_inactive']];
        return ['ok' => true, 'drift' => false, 'issues' => []];
    }
}

