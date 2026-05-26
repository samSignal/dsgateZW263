<?php

namespace App\Support\AdmissionsOffice;

use App\Support\Admissions\AdmissionAudit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionOfficeDocumentService
{
    public static function ensureReviewForUploadedDocument(int $applicationId, int $documentId, string $documentType, int $documentVersion, Request $request): void
    {
        $now = now();

        $exists = DB::table('admission_document_reviews')->where('document_id', $documentId)->exists();
        if ($exists) return;

        DB::table('admission_document_reviews')->insert([
            'application_id' => $applicationId,
            'document_id' => $documentId,
            'document_type' => $documentType,
            'document_version' => $documentVersion,
            'verification_status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
            'rejection_reason' => null,
            'verification_notes' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        AdmissionAudit::log($applicationId, 'system', null, 'admissions.office.document_review_created', [
            'document_id' => $documentId,
            'document_type' => $documentType,
            'document_version' => $documentVersion,
        ], 'info', $request);
    }

    public static function reviewAction(int $documentId, string $action, int $actorUserId, Request $request, array $payload = []): array
    {
        $now = now();

        return DB::transaction(function () use ($documentId, $action, $actorUserId, $request, $payload, $now) {
            $doc = DB::table('application_documents')->where('id', $documentId)->lockForUpdate()->first();
            if (!$doc) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Document not found.'];

            $app = DB::table('admission_applications')->where('id', (int) $doc->application_id)->lockForUpdate()->first();
            if (!$app) return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Application not found.'];

            $lifecycle = AdmissionOfficeStates::normalize($app->lifecycle_state ?? null, $app->status ?? null);
            if ($lifecycle === AdmissionOfficeStates::DUPLICATE_INVALID) {
                AdmissionAudit::log((int) $doc->application_id, 'admissions', $actorUserId, 'admissions.office.document_review_blocked', [
                    'document_id' => $documentId,
                    'blocked' => 'duplicate_invalid',
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'IMMUTABLE', 'message' => 'Duplicate-invalid applications are immutable.'];
            }
            if ($lifecycle === AdmissionOfficeStates::ARCHIVED) {
                $isMergedRelated = DB::table('admission_duplicate_links')
                    ->where('related_application_id', (int) $doc->application_id)
                    ->where('status', 'merged')
                    ->exists();
                if (!$isMergedRelated) {
                    AdmissionAudit::log((int) $doc->application_id, 'admissions', $actorUserId, 'admissions.office.document_review_blocked', [
                        'document_id' => $documentId,
                        'blocked' => 'archived',
                    ], 'warning', $request);
                    return ['ok' => false, 'code' => 'ARCHIVED', 'message' => 'Archived applications cannot be reviewed.'];
                }
            }

            $review = DB::table('admission_document_reviews')->where('document_id', $documentId)->lockForUpdate()->first();
            if (!$review) {
                DB::table('admission_document_reviews')->insert([
                    'application_id' => $doc->application_id,
                    'document_id' => $documentId,
                    'document_type' => $doc->document_type,
                    'document_version' => (int) ($doc->version ?? 1),
                    'verification_status' => 'pending',
                    'reviewed_by' => null,
                    'reviewed_at' => null,
                    'rejection_reason' => null,
                    'verification_notes' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                $review = DB::table('admission_document_reviews')->where('document_id', $documentId)->lockForUpdate()->first();
            }

            if (!empty($doc->superseded_at)) {
                AdmissionAudit::log((int) $doc->application_id, 'admissions', $actorUserId, 'admissions.office.document_review_blocked', [
                    'document_id' => $documentId,
                    'blocked' => 'superseded',
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'SUPERSEDED', 'message' => 'This document version has been superseded. Review the latest version instead.'];
            }

            if ((string) $review->verification_status === 'verified') {
                AdmissionAudit::log((int) $doc->application_id, 'admissions', $actorUserId, 'admissions.office.document_review_blocked', [
                    'document_id' => $documentId,
                    'blocked' => 'already_verified',
                ], 'warning', $request);
                return ['ok' => false, 'code' => 'LOCKED', 'message' => 'Verified documents are locked. Upload a new version to start a new review cycle.' ];
            }

            $nextStatus = match ($action) {
                'verify' => 'verified',
                'reject' => 'rejected',
                'request_reupload' => 'reupload_requested',
                default => null,
            };
            if (!$nextStatus) return ['ok' => false, 'code' => 'INVALID_ACTION', 'message' => 'Invalid document review action.'];

            $reason = trim((string) ($payload['reason'] ?? ''));
            $notes = trim((string) ($payload['notes'] ?? ''));
            if (in_array($nextStatus, ['rejected', 'reupload_requested'], true) && $reason === '') {
                return ['ok' => false, 'code' => 'REASON_REQUIRED', 'message' => 'A reason is required for this action.'];
            }

            DB::table('admission_document_reviews')->where('id', $review->id)->update([
                'verification_status' => $nextStatus,
                'reviewed_by' => $actorUserId,
                'reviewed_at' => $now,
                'rejection_reason' => $reason !== '' ? $reason : null,
                'verification_notes' => $notes !== '' ? $notes : null,
                'updated_at' => $now,
            ]);

            AdmissionAudit::log((int) $doc->application_id, 'admissions', $actorUserId, 'admissions.office.document_review', [
                'document_id' => $documentId,
                'document_type' => (string) $doc->document_type,
                'document_version' => (int) ($doc->version ?? 1),
                'status' => $nextStatus,
                'reason' => $reason !== '' ? $reason : null,
            ], 'info', $request);

            return ['ok' => true, 'application_id' => (int) $doc->application_id, 'verification_status' => $nextStatus];
        });
    }
}
