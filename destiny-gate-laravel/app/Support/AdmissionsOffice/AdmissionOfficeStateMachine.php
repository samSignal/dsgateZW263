<?php

namespace App\Support\AdmissionsOffice;

use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionRequirements;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionOfficeStateMachine
{
    private static function decisionApplicationIds(int $applicationId): array
    {
        $mergedRelated = DB::table('admission_duplicate_links')
            ->where('canonical_application_id', $applicationId)
            ->where('status', 'merged')
            ->pluck('related_application_id')
            ->map(fn ($v) => (int) $v)
            ->all();

        $ids = array_values(array_unique(array_merge([$applicationId], $mergedRelated)));
        return $ids;
    }

    public static function canTransition(string $from, string $to): bool
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        $allowed = [
            AdmissionOfficeStates::SUBMITTED => [
                AdmissionOfficeStates::UNDER_REVIEW,
                AdmissionOfficeStates::DOCUMENTS_REQUIRED,
                AdmissionOfficeStates::DUPLICATE_FLAGGED,
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::UNDER_REVIEW => [
                AdmissionOfficeStates::DOCUMENTS_REQUIRED,
                AdmissionOfficeStates::READY_FOR_DECISION,
                AdmissionOfficeStates::DUPLICATE_FLAGGED,
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::DOCUMENTS_REQUIRED => [
                AdmissionOfficeStates::UNDER_REVIEW,
                AdmissionOfficeStates::DUPLICATE_FLAGGED,
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::READY_FOR_DECISION => [
                AdmissionOfficeStates::DOCUMENTS_REQUIRED,
                AdmissionOfficeStates::ACCEPTED,
                AdmissionOfficeStates::REJECTED,
                AdmissionOfficeStates::WAITLISTED,
                AdmissionOfficeStates::DUPLICATE_FLAGGED,
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::DUPLICATE_FLAGGED => [
                AdmissionOfficeStates::UNDER_REVIEW,
                AdmissionOfficeStates::DUPLICATE_INVALID,
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::ACCEPTED => [
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::REJECTED => [
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::WAITLISTED => [
                AdmissionOfficeStates::ARCHIVED,
            ],
            AdmissionOfficeStates::ARCHIVED => [],
            AdmissionOfficeStates::DUPLICATE_INVALID => [],
        ];

        return in_array($to, $allowed[$from] ?? [], true);
    }

    public static function transition(int $applicationId, string $toState, string $reason, Request $request, array $meta = []): array
    {
        $toState = strtoupper(trim($toState));
        $now = now();
        $user = $request->user();

        return DB::transaction(function () use ($applicationId, $toState, $reason, $request, $meta, $now, $user) {
            if (!AdmissionOfficeStates::isValid($toState)) {
                AdmissionAudit::log($applicationId, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'to' => $toState,
                    'reason' => $reason,
                    'blocked' => 'invalid_state',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'INVALID_STATE', 'message' => 'Invalid target state.'];
            }

            $app = DB::table('admission_applications')->where('id', $applicationId)->lockForUpdate()->first();
            if (!$app) {
                AdmissionAudit::log($applicationId, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'to' => $toState,
                    'reason' => $reason,
                    'blocked' => 'not_found',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];
            }

            $current = AdmissionOfficeStates::normalize($app->lifecycle_state ?? null, $app->status ?? null);
            if (!$current) $current = AdmissionOfficeStates::SUBMITTED;
            $override = (bool) ($meta['override'] ?? false);
            $force = (bool) ($meta['force'] ?? false);

            if (in_array((string) ($app->lifecycle_state ?? ''), ['DRAFT_ACTIVE', 'DRAFT_REACTIVATED', 'DRAFT_EXPIRED_ARCHIVED'], true) || (string) $app->status === 'draft') {
                AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'from' => $current,
                    'to' => $toState,
                    'reason' => $reason,
                    'blocked' => 'draft',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'NOT_REVIEWABLE', 'message' => 'Draft applications cannot be reviewed in admissions office workflow.'];
            }

            if ($current === AdmissionOfficeStates::ARCHIVED && !$override) {
                AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'from' => $current,
                    'to' => $toState,
                    'reason' => $reason,
                    'blocked' => 'archived',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'ARCHIVED', 'message' => 'Archived applications cannot be reviewed.'];
            }

            if ($current === AdmissionOfficeStates::DUPLICATE_INVALID && !$override) {
                AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'from' => $current,
                    'to' => $toState,
                    'reason' => $reason,
                    'blocked' => 'duplicate_invalid',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'IMMUTABLE', 'message' => 'Duplicate-invalid applications are immutable.'];
            }

            if (!self::canTransition($current, $toState) && !$override) {
                AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'from' => $current,
                    'to' => $toState,
                    'reason' => $reason,
                    'blocked' => 'invalid_transition',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'INVALID_TRANSITION', 'message' => 'This state transition is not allowed.'];
            }

            if (in_array($toState, [AdmissionOfficeStates::REJECTED, AdmissionOfficeStates::WAITLISTED], true) && trim($reason) === '') {
                AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                    'from' => $current,
                    'to' => $toState,
                    'blocked' => 'missing_reason',
                ] + $meta, 'warning', $request);
                return ['ok' => false, 'code' => 'REASON_REQUIRED', 'message' => 'A reason is required for this decision.' ];
            }

            if ($toState === AdmissionOfficeStates::READY_FOR_DECISION || in_array($toState, [AdmissionOfficeStates::ACCEPTED, AdmissionOfficeStates::REJECTED, AdmissionOfficeStates::WAITLISTED], true)) {
                $decisionIds = self::decisionApplicationIds((int) $app->id);
                $pendingRequests = DB::table('admission_document_reviews')
                    ->whereIn('application_id', $decisionIds)
                    ->where('verification_status', 'reupload_requested')
                    ->count();

                if ($toState === AdmissionOfficeStates::READY_FOR_DECISION && $pendingRequests > 0) {
                    AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                        'from' => $current,
                        'to' => $toState,
                        'reason' => $reason,
                        'blocked' => 'pending_document_requests',
                    ] + $meta, 'warning', $request);
                    return ['ok' => false, 'code' => 'DOCS_PENDING', 'message' => 'Cannot move to READY_FOR_DECISION while document requests are outstanding.'];
                }

                if ($toState === AdmissionOfficeStates::ACCEPTED && !$force) {
                    $requiredTypes = AdmissionRequirements::requiredDocuments((string) $app->application_type);
                    $verifiedTypes = DB::table('application_documents as d')
                        ->join('admission_document_reviews as r', 'r.document_id', '=', 'd.id')
                        ->whereIn('d.application_id', $decisionIds)
                        ->whereNull('d.superseded_at')
                        ->whereIn('d.document_type', $requiredTypes)
                        ->where('r.verification_status', 'verified')
                        ->distinct()
                        ->pluck('d.document_type')
                        ->map(fn ($v) => (string) $v)
                        ->all();

                    $missing = array_values(array_diff($requiredTypes, $verifiedTypes));
                    if (count($missing) > 0) {
                        AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition_blocked', [
                            'from' => $current,
                            'to' => $toState,
                            'reason' => $reason,
                            'blocked' => 'docs_not_verified',
                            'missing_document_types' => $missing,
                        ] + $meta, 'warning', $request);
                        return ['ok' => false, 'code' => 'DOCS_NOT_VERIFIED', 'message' => 'Cannot ACCEPT without verified mandatory documents.'];
                    }
                }
            }

            $status = AdmissionOfficeStates::mapToStatus($toState, (string) $app->status);
            $updates = [
                'lifecycle_state' => $toState,
                'status' => $status,
                'status_updated_at' => $now,
                'updated_at' => $now,
            ];

            if ($toState === AdmissionOfficeStates::UNDER_REVIEW && empty($app->review_started_at)) {
                $updates['review_started_at'] = $now;
            }
            if (in_array($toState, [AdmissionOfficeStates::READY_FOR_DECISION, AdmissionOfficeStates::ACCEPTED, AdmissionOfficeStates::REJECTED, AdmissionOfficeStates::WAITLISTED], true)) {
                $updates['review_completed_at'] = $now;
            }

            DB::table('admission_applications')->where('id', $app->id)->update($updates);

            if ($override) {
                AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.override_performed', [
                    'from' => $current,
                    'to' => $toState,
                    'reason' => $reason,
                    'force' => $force,
                ] + $meta, 'warning', $request);
            }

            AdmissionAudit::log((int) $app->id, 'admissions', $user?->id, 'admissions.office.transition', [
                'from' => $current,
                'to' => $toState,
                'reason' => $reason,
            ] + $meta, 'info', $request);

            return ['ok' => true, 'from' => $current, 'to' => $toState, 'status' => $status];
        });
    }
}
