<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionAccessService;
use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionIntakeService;
use App\Support\Admissions\AdmissionRequirements;
use App\Support\Admissions\AdmissionResponder;
use App\Support\Admissions\AdmissionSecurityHasher;
use App\Support\Admissions\AdmissionSecurityService;
use App\Support\Admissions\AdmissionSessionService;
use App\Support\AdmissionsOffice\AdmissionOfficeDocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentController extends Controller
{
    public function upload(Request $request)
    {
        [$app, $session] = AdmissionAccessService::resolveFromRequest($request);
        if (!$app) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Token verification failed.', 403);
        }
        if (!empty($app->archived_at)) {
            return AdmissionResponder::fail('DRAFT_ARCHIVED', 'This draft is archived and cannot be edited online.', 409);
        }
        if (in_array((string) ($app->lifecycle_state ?? ''), ['DUPLICATE_INVALID', 'ARCHIVED'], true)) {
            return AdmissionResponder::fail('UPLOAD_NOT_ALLOWED', 'Document uploads are not allowed for this application state.', 409);
        }

        $state = (string) ($app->lifecycle_state ?? '');
        $status = (string) ($app->status ?? '');
        $allowed = in_array($state, ['DRAFT_ACTIVE', 'DRAFT_REACTIVATED', 'DOCUMENTS_REQUIRED', 'SUBMITTED', 'UNDER_REVIEW'], true) || in_array($status, ['draft', 'documents_required', 'submitted', 'under_review'], true);
        if (!$allowed) {
            return AdmissionResponder::fail('UPLOAD_NOT_ALLOWED', 'Document uploads are not allowed for this application state.', 409);
        }

        if ($status === 'draft' && config('admissions.intake.require_open_intake_for_drafts', true)) {
            if (!AdmissionIntakeService::isIntakeOpen((int) $app->academic_year_id)) {
                DB::table('admission_applications')->where('id', $app->id)->update([
                    'archived_at' => now(),
                    'archived_reason' => 'archived_intake_closed',
                    'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                    'updated_at' => now(),
                ]);
                AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.lifecycle.archived_intake_closed', ['academic_year_id' => (int) $app->academic_year_id], 'info', $request);
                return AdmissionResponder::fail('INTAKE_CLOSED', 'This admissions intake is now closed.', 409, [], [
                    'intake_close_at' => AdmissionIntakeService::intakeCloseAt((int) $app->academic_year_id),
                ]);
            }
        }

        $isLocked = !empty($app->locked_at) || in_array($state, ['SUBMITTED', 'UNDER_REVIEW'], true) || in_array($status, ['submitted', 'under_review'], true);
        if ($isLocked) {
            if (!$session || !AdmissionSessionService::hasStepupForScope($session, 'documents')) {
                $ipHash = AdmissionSecurityHasher::ipHash($request->ip()) ?? 'noip';
                $threshold = (int) config('admissions.suspicious.doc_replace_attempt_threshold', 10);
                $lockout = (int) config('admissions.suspicious.lockout_minutes', 15);

                if (AdmissionSecurityService::isLocked('doc_replace_attempt_ip', $ipHash)) {
                    AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.lockout_blocked', ['type' => 'doc_replace_attempt_ip'], 'warning', $request);
                    return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
                }

                $result = AdmissionSecurityService::recordFailure('doc_replace_attempt_ip', $ipHash, $threshold, $lockout);
                if (!empty($result['locked_until'])) {
                    AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.lockout_applied', ['type' => 'doc_replace_attempt_ip'], 'warning', $request);
                    return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
                }

                return AdmissionResponder::fail('STEPUP_REQUIRED', 'Additional verification is required to replace documents after submission.', 403, [], [
                    'required_action' => 'replace_documents',
                ]);
            }
        }

        $validated = $request->validate([
            'document' => 'required|file|mimes:pdf,jpg,jpeg,png,webp,gif|max:5120',
            'document_type' => 'required|in:birth_certificate,passport_photo,grade7_report,latest_report,transfer_letter,discipline_record,medical_record',
        ]);

        $docType = (string) $validated['document_type'];
        $file = $validated['document'];

        $now = now();
        $existing = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->where('document_type', $docType)
            ->whereNull('superseded_at')
            ->orderByDesc('version')
            ->first();

        $nextVersion = (int) (($existing?->version ?? 0) + 1);
        $folder = 'admissions/documents/' . (string) $app->application_number;
        $fileName = Str::slug($docType) . '-v' . $nextVersion . '-' . Str::random(12) . '.' . $file->getClientOriginalExtension();

        $path = $file->storeAs($folder, $fileName, 'public');

        DB::beginTransaction();
        try {
            $newId = DB::table('application_documents')->insertGetId([
                'application_id' => $app->id,
                'document_type' => $docType,
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'version' => $nextVersion,
                'uploaded_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AdmissionOfficeDocumentService::ensureReviewForUploadedDocument((int) $app->id, (int) $newId, $docType, $nextVersion, $request);

            if ($existing) {
                DB::table('application_documents')->where('id', $existing->id)->update([
                    'superseded_at' => $now,
                    'superseded_by' => $newId,
                    'updated_at' => $now,
                ]);
            }

            DB::table('admission_applications')->where('id', $app->id)->update([
                'last_activity_at' => $now,
                'updated_at' => $now,
            ]);

            AdmissionAudit::log((int) $app->id, 'applicant', null, 'admissions.v2.document_uploaded', [
                'document_type' => $docType,
                'version' => $nextVersion,
                'superseded_document_id' => $existing?->id,
            ], 'info', $request);

            DB::commit();

            $required = AdmissionRequirements::requiredDocuments((string) $app->application_type);
            return AdmissionResponder::ok([
                'application_id' => (int) $app->id,
                'document_id' => (int) $newId,
                'document_type' => $docType,
                'version' => $nextVersion,
                'file_path' => $path,
                'required_documents' => $required,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            if (!empty($path)) {
                Storage::disk('public')->delete($path);
            }
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.document_upload_failed', ['error' => $e->getMessage(), 'document_type' => $docType], 'critical', $request);
            return AdmissionResponder::fail('UPLOAD_FAILED', 'Failed to upload document. Please try again.', 500);
        }
    }
}
