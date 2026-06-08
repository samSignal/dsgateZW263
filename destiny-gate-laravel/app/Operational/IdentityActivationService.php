<?php

namespace App\Operational;

use Illuminate\Support\Facades\DB;

class IdentityActivationService
{
    public static function activate(array $ctx, bool $dryRun): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        $applicationId = (int) ($ctx['application_id'] ?? 0);
        if ($studentId <= 0 || $applicationId <= 0) return ['ok' => false, 'code' => 'INVALID', 'message' => 'Missing identifiers.'];

        return DB::transaction(function () use ($studentId, $applicationId, $ctx, $dryRun) {
            $student = DB::table('students')->where('id', $studentId)->lockForUpdate()->first(['id', 'user_id']);
            if (!$student) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Student not found.'];

            $app = DB::table('admission_applications')->where('id', $applicationId)->lockForUpdate()->first(['id', 'guardian_user_id', 'student_user_id']);
            if (!$app) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            $studentUserId = $student->user_id !== null ? (int) $student->user_id : ($app->student_user_id !== null ? (int) $app->student_user_id : 0);
            $guardianUserId = $app->guardian_user_id !== null ? (int) $app->guardian_user_id : 0;

            if ($studentUserId <= 0) return ['ok' => false, 'code' => 'MISSING', 'message' => 'Student user is not provisioned.'];
            if ($guardianUserId <= 0) return ['ok' => false, 'code' => 'MISSING', 'message' => 'Guardian user is not provisioned.'];

            $su = DB::table('users')->where('id', $studentUserId)->lockForUpdate()->first(['id', 'is_active', 'role']);
            $gu = DB::table('users')->where('id', $guardianUserId)->lockForUpdate()->first(['id', 'is_active', 'role']);
            if (!$su || !$gu) return ['ok' => false, 'code' => 'MISSING', 'message' => 'Provisioned identities are missing.'];

            $already = ((bool) $su->is_active) && ((bool) $gu->is_active);
            if ($already) {
                return [
                    'ok' => true,
                    'code' => 'OK',
                    'idempotent' => true,
                    'student_user_id' => $studentUserId,
                    'guardian_user_id' => $guardianUserId,
                    'rollback_meta' => ['created' => false],
                ];
            }

            if ($dryRun) {
                return [
                    'ok' => true,
                    'code' => 'DRY_RUN',
                    'idempotent' => false,
                    'student_user_id' => $studentUserId,
                    'guardian_user_id' => $guardianUserId,
                    'rollback_meta' => ['created' => false],
                ];
            }

            DB::table('users')->whereIn('id', [$studentUserId, $guardianUserId])->update([
                'is_active' => true,
                'updated_at' => now(),
            ]);

            $ref = 'ID-' . strtoupper(substr(hash('sha256', 'id|' . $studentId . '|' . $applicationId), 0, 12));
            $marker = OperationalMarkerService::activate($studentId, 'identity', $ref, [
                'student_user_id' => $studentUserId,
                'guardian_user_id' => $guardianUserId,
                'idempotency_key' => $ctx['idempotency_key'] ?? null,
            ], false);

            return [
                'ok' => true,
                'code' => 'OK',
                'idempotent' => false,
                'student_user_id' => $studentUserId,
                'guardian_user_id' => $guardianUserId,
                'rollback_meta' => [
                    'created' => true,
                    'student_user_id' => $studentUserId,
                    'guardian_user_id' => $guardianUserId,
                    'student_user_prev_active' => (bool) $su->is_active,
                    'guardian_user_prev_active' => (bool) $gu->is_active,
                    'marker_id' => $marker['marker_id'] ?? null,
                ],
            ];
        });
    }

    public static function rollback(array $ctx, array $rollbackMeta): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        $studentUserId = (int) ($rollbackMeta['student_user_id'] ?? 0);
        $guardianUserId = (int) ($rollbackMeta['guardian_user_id'] ?? 0);
        $suPrev = (bool) ($rollbackMeta['student_user_prev_active'] ?? false);
        $guPrev = (bool) ($rollbackMeta['guardian_user_prev_active'] ?? false);

        return DB::transaction(function () use ($studentId, $studentUserId, $guardianUserId, $suPrev, $guPrev, $rollbackMeta) {
            if ($studentUserId > 0) DB::table('users')->where('id', $studentUserId)->update(['is_active' => $suPrev, 'updated_at' => now()]);
            if ($guardianUserId > 0) DB::table('users')->where('id', $guardianUserId)->update(['is_active' => $guPrev, 'updated_at' => now()]);
            if ($studentId > 0) OperationalMarkerService::rollback($studentId, 'identity', ['marker_id' => $rollbackMeta['marker_id'] ?? null]);
            return ['ok' => true, 'code' => 'OK'];
        });
    }

    public static function reconcile(array $ctx): array
    {
        $studentId = (int) ($ctx['student_id'] ?? 0);
        $applicationId = (int) ($ctx['application_id'] ?? 0);
        if ($studentId <= 0 || $applicationId <= 0) return ['ok' => false, 'drift' => true, 'issues' => ['missing_context']];

        $student = DB::table('students')->where('id', $studentId)->first(['user_id']);
        $app = DB::table('admission_applications')->where('id', $applicationId)->first(['guardian_user_id', 'student_user_id']);
        $issues = [];
        if (!$student || $student->user_id === null) $issues[] = 'student_user_missing';
        if (!$app || $app->guardian_user_id === null) $issues[] = 'guardian_user_missing';

        if ($student && $student->user_id) {
            $u = DB::table('users')->where('id', (int) $student->user_id)->first(['is_active']);
            if (!$u || empty($u->is_active)) $issues[] = 'student_user_inactive';
        }
        if ($app && $app->guardian_user_id) {
            $u = DB::table('users')->where('id', (int) $app->guardian_user_id)->first(['is_active']);
            if (!$u || empty($u->is_active)) $issues[] = 'guardian_user_inactive';
        }

        $m = OperationalMarkerService::reconcile($studentId, 'identity');
        if (empty($m['ok'])) $issues[] = 'identity_marker_missing';

        return ['ok' => count($issues) === 0, 'drift' => count($issues) > 0, 'issues' => $issues];
    }
}

