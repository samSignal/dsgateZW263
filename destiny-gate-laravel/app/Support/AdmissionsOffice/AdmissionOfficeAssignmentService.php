<?php

namespace App\Support\AdmissionsOffice;

use App\Support\Admissions\AdmissionAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionOfficeAssignmentService
{
    public static function claim(int $applicationId, int $actorUserId, Request $request): array
    {
        $now = now();
        return DB::transaction(function () use ($applicationId, $actorUserId, $request, $now) {
            $app = DB::table('admission_applications')->where('id', $applicationId)->lockForUpdate()->first();
            if (!$app) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            $lifecycle = AdmissionOfficeStates::normalize($app->lifecycle_state ?? null, $app->status ?? null);
            if (in_array($lifecycle, [AdmissionOfficeStates::ARCHIVED, AdmissionOfficeStates::DUPLICATE_INVALID], true)) {
                AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_claim_blocked', [
                    'blocked' => strtolower((string) $lifecycle),
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'NOT_REVIEWABLE', 'message' => 'This application cannot be claimed for review in its current state.'];
            }

            $assigned = $app->assigned_reviewer_id ? (int) $app->assigned_reviewer_id : null;
            if ($assigned && $assigned !== $actorUserId) {
                AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_claim_blocked', [
                    'assigned_reviewer_id' => $assigned,
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'ALREADY_CLAIMED', 'message' => 'This application is currently reviewed by another staff member.', 'assigned_reviewer_id' => $assigned];
            }

            if (!$assigned) {
                DB::table('admission_applications')->where('id', $app->id)->update([
                    'assigned_reviewer_id' => $actorUserId,
                    'assigned_at' => $now,
                    'review_started_at' => $app->review_started_at ?? $now,
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_claimed', [], 'info', $request);
            }

            return ['ok' => true, 'assigned_reviewer_id' => $actorUserId, 'assigned_at' => (string) $now];
        });
    }

    public static function assign(int $applicationId, int $actorUserId, int $assigneeUserId, string $reason, Request $request): array
    {
        $now = now();
        return DB::transaction(function () use ($applicationId, $actorUserId, $assigneeUserId, $reason, $request, $now) {
            $app = DB::table('admission_applications')->where('id', $applicationId)->lockForUpdate()->first();
            if (!$app) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            $lifecycle = AdmissionOfficeStates::normalize($app->lifecycle_state ?? null, $app->status ?? null);
            if (in_array($lifecycle, [AdmissionOfficeStates::ARCHIVED, AdmissionOfficeStates::DUPLICATE_INVALID], true)) {
                AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_assign_blocked', [
                    'blocked' => strtolower((string) $lifecycle),
                    'assigned_reviewer_id' => $assigneeUserId,
                    'reason' => $reason,
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'NOT_REVIEWABLE', 'message' => 'This application cannot be assigned for review in its current state.'];
            }

            DB::table('admission_applications')->where('id', $app->id)->update([
                'assigned_reviewer_id' => $assigneeUserId,
                'assigned_at' => $now,
                'updated_at' => $now,
            ]);

            AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_assigned', [
                'assigned_reviewer_id' => $assigneeUserId,
                'reason' => $reason,
            ], 'info', $request);

            return ['ok' => true, 'assigned_reviewer_id' => $assigneeUserId, 'assigned_at' => (string) $now];
        });
    }

    public static function release(int $applicationId, int $actorUserId, string $reason, bool $force, Request $request): array
    {
        $now = now();
        return DB::transaction(function () use ($applicationId, $actorUserId, $reason, $force, $request, $now) {
            $app = DB::table('admission_applications')->where('id', $applicationId)->lockForUpdate()->first();
            if (!$app) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            $lifecycle = AdmissionOfficeStates::normalize($app->lifecycle_state ?? null, $app->status ?? null);
            if (in_array($lifecycle, [AdmissionOfficeStates::ARCHIVED, AdmissionOfficeStates::DUPLICATE_INVALID], true) && !$force) {
                AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_release_blocked', [
                    'blocked' => strtolower((string) $lifecycle),
                    'reason' => $reason,
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'NOT_REVIEWABLE', 'message' => 'This application cannot be released in its current state.'];
            }

            $assigned = $app->assigned_reviewer_id ? (int) $app->assigned_reviewer_id : null;
            if ($assigned && $assigned !== $actorUserId && !$force) {
                return ['ok' => false, 'code' => 'FORBIDDEN', 'message' => 'Only the assigned reviewer can release this review.' ];
            }

            DB::table('admission_applications')->where('id', $app->id)->update([
                'assigned_reviewer_id' => null,
                'assigned_at' => null,
                'updated_at' => $now,
            ]);

            AdmissionAudit::log((int) $app->id, 'admissions', $actorUserId, 'admissions.office.review_released', [
                'reason' => $reason,
                'force' => $force,
            ], 'info', $request);

            return ['ok' => true];
        });
    }
}
