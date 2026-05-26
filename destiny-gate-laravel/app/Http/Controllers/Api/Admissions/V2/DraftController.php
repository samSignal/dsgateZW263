<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionAccessService;
use App\Support\Admissions\AdmissionDuplicateService;
use App\Support\Admissions\AdmissionIntakeService;
use App\Support\Admissions\AdmissionNormalizer;
use App\Support\Admissions\AdmissionResponder;
use App\Support\Admissions\AdmissionSecurityHasher;
use App\Support\Admissions\AdmissionSessionService;
use App\Support\Admissions\AdmissionTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DraftController extends Controller
{
    public function start(Request $request)
    {
        $data = $request->validate([
            'application_type' => 'required|in:new_intake,transfer',
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'applying_form_id' => 'required|integer|exists:forms,id',
            'preferred_category_id' => 'required|integer|exists:categories,id',
            'student_first_name' => 'required|string|max:100',
            'student_last_name' => 'required|string|max:100',
            'gender' => 'nullable|in:male,female,other',
            'date_of_birth' => 'required|date',
            'birth_certificate_number' => 'required|string|max:80',
            'student_national_id' => 'nullable|string|max:50',
            'guardian_name' => 'nullable|string|max:100',
            'guardian_national_id' => 'nullable|string|max:50',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_email' => 'nullable|email|max:320',
            'emergency_phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:2000',
            'occupation' => 'nullable|string|max:120',
        ]);

        $birthNorm = AdmissionNormalizer::normalizeBirthCertificate($data['birth_certificate_number']);
        if (!$birthNorm) {
            return AdmissionResponder::fail('INVALID_BIRTH_CERT', 'Birth certificate number is invalid.', 422, ['birth_certificate_number' => ['Invalid value.']]);
        }

        if (config('admissions.intake.require_open_intake_for_drafts', true)) {
            if (!AdmissionIntakeService::isIntakeOpen((int) $data['academic_year_id'])) {
                AdmissionAudit::log(null, 'system', null, 'admissions.v2.intake_closed_block_draft_start', [
                    'academic_year_id' => (int) $data['academic_year_id'],
                ], 'warning', $request);
                return AdmissionResponder::fail('INTAKE_CLOSED', 'This admissions intake is now closed.', 409, [], [
                    'intake_close_at' => AdmissionIntakeService::intakeCloseAt((int) $data['academic_year_id']),
                ]);
            }
        }

        $existingSubmittedId = AdmissionDuplicateService::findSubmittedApplicationId((int) $data['academic_year_id'], $birthNorm);
        if ($existingSubmittedId) {
            AdmissionAudit::log(null, 'system', null, 'admissions.v2.duplicate_submitted_block', [
                'academic_year_id' => (int) $data['academic_year_id'],
                'birth_certificate_hash' => AdmissionSecurityHasher::keyHash('bc:' . $birthNorm),
            ], 'warning', $request);

            return AdmissionResponder::fail(
                'DUPLICATE_SUBMITTED',
                'We cannot accept another submission for this learner for the selected intake year.',
                409,
                [],
                ['recovery' => ['track_requires' => ['token', 'date_of_birth'], 'recover_access' => true]]
            );
        }

        $existingDraftId = AdmissionDuplicateService::findActiveDraftId((int) $data['academic_year_id'], $birthNorm);
        if ($existingDraftId) {
            AdmissionAudit::log($existingDraftId, 'system', null, 'admissions.v2.duplicate_draft_exists', [
                'academic_year_id' => (int) $data['academic_year_id'],
                'birth_certificate_hash' => AdmissionSecurityHasher::keyHash('bc:' . $birthNorm),
            ], 'warning', $request);

            return AdmissionResponder::fail(
                'DRAFT_EXISTS',
                'A draft already exists for this learner and intake year. Please resume with your token or recover access.',
                409,
                [],
                ['recovery' => ['recover_access' => true]]
            );
        }

        DB::beginTransaction();
        try {
            $appNumber = $this->generateApplicationNumber();
            $token = AdmissionTokenService::generatePlainToken();
            $tokenHash = AdmissionTokenService::hashToken($token);
            $legacyToken = 'V2-' . Str::upper(Str::random(16));

            $now = now();
            $expiresAt = $now->copy()->addDays((int) config('admissions.inactivity_expiry_days', 90));
            $intakeCloseAt = AdmissionIntakeService::intakeCloseAt((int) $data['academic_year_id']);
            if ($intakeCloseAt) {
                $close = \Illuminate\Support\Carbon::parse($intakeCloseAt);
                if ($close->lt($expiresAt)) $expiresAt = $close;
            }

            $id = DB::table('admission_applications')->insertGetId([
                'application_number' => $appNumber,
                'tracking_token' => $legacyToken,
                'application_type' => $data['application_type'],
                'academic_year_id' => (int) $data['academic_year_id'],
                'term_id' => null,
                'applying_form_id' => (int) $data['applying_form_id'],
                'preferred_category_id' => (int) $data['preferred_category_id'],
                'student_first_name' => AdmissionNormalizer::normalizeName($data['student_first_name']),
                'student_last_name' => AdmissionNormalizer::normalizeName($data['student_last_name']),
                'gender' => $data['gender'] ?? null,
                'date_of_birth' => $data['date_of_birth'],
                'birth_certificate_number' => strtoupper(trim($data['birth_certificate_number'])),
                'birth_certificate_number_normalized' => $birthNorm,
                'student_national_id' => $data['student_national_id'] ?? null,
                'passport_photo' => null,
                'guardian_name' => AdmissionNormalizer::normalizeName($data['guardian_name'] ?? null),
                'guardian_national_id' => $data['guardian_national_id'] ?? null,
                'guardian_phone' => $data['guardian_phone'] ?? null,
                'emergency_phone' => $data['emergency_phone'] ?? null,
                'guardian_email' => AdmissionNormalizer::normalizeEmail($data['guardian_email'] ?? null),
                'address' => $data['address'] ?? null,
                'occupation' => $data['occupation'] ?? null,
                'status' => 'draft',
                'lifecycle_state' => 'DRAFT_ACTIVE',
                'current_step' => 2,
                'draft_revision' => 0,
                'is_submitted' => false,
                'progress_percentage' => 0,
                'completion_percentage' => 0,
                'draft_last_saved_at' => $now,
                'last_activity_at' => $now,
                'expires_at' => $expiresAt,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('admission_application_tokens')->insert([
                'application_id' => $id,
                'purpose' => 'access',
                'token_hash' => $tokenHash,
                'token_last4' => AdmissionTokenService::tokenLast4($token),
                'created_ip' => $request->ip(),
                'created_ip_hash' => AdmissionSecurityHasher::ipHash($request->ip()),
                'created_device_hash' => AdmissionSecurityHasher::deviceHash($request),
                'created_user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 500) : null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            AdmissionAudit::log($id, 'system', null, 'admissions.v2.draft_started', [
                'application_number' => $appNumber,
                'academic_year_id' => (int) $data['academic_year_id'],
                'applying_form_id' => (int) $data['applying_form_id'],
                'preferred_category_id' => (int) $data['preferred_category_id'],
            ], 'info', $request);

            DB::commit();

            return AdmissionResponder::ok([
                'application_id' => $id,
                'application_number' => $appNumber,
                'issued_token' => $token,
                'token_last4' => AdmissionTokenService::tokenLast4($token),
                'lifecycle_state' => 'DRAFT_ACTIVE',
                'draft_revision' => 0,
                'expires_at' => $expiresAt,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            AdmissionAudit::log(null, 'system', null, 'admissions.v2.draft_start_failed', ['error' => $e->getMessage()], 'critical', $request);
            return AdmissionResponder::fail('DRAFT_START_FAILED', 'Failed to start draft. Please try again.', 500);
        }
    }

    public function save(Request $request)
    {
        [$app, $session] = AdmissionAccessService::resolveFromRequest($request);
        if (!$app) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Token verification failed.', 403);
        }
        if (in_array((string) ($app->lifecycle_state ?? ''), ['DUPLICATE_INVALID', 'ARCHIVED'], true)) {
            return AdmissionResponder::fail('NOT_EDITABLE', 'This application is not editable.', 409);
        }
        if (!in_array((string) ($app->lifecycle_state ?? ''), ['DRAFT_ACTIVE', 'DRAFT_REACTIVATED'], true) && (string) $app->status !== 'draft') {
            return AdmissionResponder::fail('NOT_EDITABLE', 'This application is not editable.', 409);
        }
        if (!empty($app->archived_at)) {
            return AdmissionResponder::fail('DRAFT_ARCHIVED', 'This draft is archived and cannot be edited online.', 409);
        }
        if (!empty($app->expires_at) && now()->gte($app->expires_at)) {
            DB::table('admission_applications')->where('id', $app->id)->update([
                'archived_at' => now(),
                'archived_reason' => 'archived_inactive',
                'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                'updated_at' => now(),
            ]);
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.lifecycle.archived_inactive', [], 'info', $request);
            return AdmissionResponder::fail('DRAFT_ARCHIVED', 'This draft has expired due to inactivity.', 409);
        }

        $data = $request->validate([
            'draft_revision' => 'required|integer|min:0',
            'current_step' => 'required|integer|min:1|max:6',
            'application_type' => 'sometimes|in:new_intake,transfer',
            'academic_year_id' => 'sometimes|integer|exists:academic_years,id',
            'applying_form_id' => 'sometimes|integer|exists:forms,id',
            'preferred_category_id' => 'sometimes|integer|exists:categories,id',
            'student_first_name' => 'sometimes|string|max:100',
            'student_last_name' => 'sometimes|string|max:100',
            'gender' => 'sometimes|nullable|in:male,female,other',
            'date_of_birth' => 'sometimes|date',
            'birth_certificate_number' => 'sometimes|string|max:80',
            'student_national_id' => 'sometimes|nullable|string|max:50',
            'guardian_name' => 'sometimes|string|max:100',
            'guardian_national_id' => 'sometimes|nullable|string|max:50',
            'guardian_phone' => 'sometimes|string|max:20',
            'guardian_email' => 'sometimes|email|max:320',
            'emergency_phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|nullable|string|max:2000',
            'occupation' => 'sometimes|nullable|string|max:120',
            'grade7_school' => 'sometimes|nullable|string|max:150',
            'grade7_results' => 'sometimes|nullable|string|max:255',
            'previous_school_name' => 'sometimes|nullable|string|max:150',
            'current_form' => 'sometimes|nullable|string|max:50',
            'transfer_reason' => 'sometimes|nullable|string',
            'last_term_average' => 'sometimes|nullable|string|max:50',
            'reason_for_joining' => 'sometimes|nullable|string',
            'medical_information' => 'sometimes|nullable|string',
        ]);

        $expectedRevision = (int) ($app->draft_revision ?? 0);
        if ((int) $data['draft_revision'] !== $expectedRevision) {
            return AdmissionResponder::fail('REVISION_CONFLICT', 'This draft was updated from another device. Refresh and try again.', 409, [], [
                'current_draft_revision' => $expectedRevision,
                'current_step' => (int) ($app->current_step ?? 1),
            ]);
        }

        $requestedIdentityChange = false;
        $requestedGuardianContactChange = false;

        if ($request->has('student_first_name') && AdmissionNormalizer::normalizeName((string) $data['student_first_name']) !== (string) $app->student_first_name) {
            $requestedIdentityChange = true;
        }
        if ($request->has('student_last_name') && AdmissionNormalizer::normalizeName((string) $data['student_last_name']) !== (string) $app->student_last_name) {
            $requestedIdentityChange = true;
        }
        if ($request->has('date_of_birth') && (string) $data['date_of_birth'] !== (string) $app->date_of_birth) {
            $requestedIdentityChange = true;
        }
        if ($request->has('birth_certificate_number')) {
            $incoming = (string) $data['birth_certificate_number'];
            $incomingNorm = AdmissionNormalizer::normalizeBirthCertificate($incoming);
            if (!$incomingNorm) {
                return AdmissionResponder::fail('INVALID_BIRTH_CERT', 'Birth certificate number is invalid.', 422, ['birth_certificate_number' => ['Invalid value.']]);
            }
            if ((string) $incomingNorm !== (string) ($app->birth_certificate_number_normalized ?? '')) {
                $requestedIdentityChange = true;
            }
        }

        $storedGuardianEmail = AdmissionNormalizer::normalizeEmail($app->guardian_email ?? null);
        if ($request->has('guardian_email')) {
            $incomingEmail = AdmissionNormalizer::normalizeEmail((string) $data['guardian_email']);
            if ($storedGuardianEmail && $incomingEmail && $incomingEmail !== $storedGuardianEmail) {
                $requestedGuardianContactChange = true;
            }
        }

        $storedGuardianPhone = trim((string) ($app->guardian_phone ?? ''));
        if ($request->has('guardian_phone')) {
            $incomingPhone = trim((string) $data['guardian_phone']);
            if ($storedGuardianPhone !== '' && $incomingPhone !== '' && $incomingPhone !== $storedGuardianPhone) {
                $requestedGuardianContactChange = true;
            }
        }

        if ($requestedIdentityChange || $requestedGuardianContactChange) {
            if (!$session) {
                return AdmissionResponder::fail('STEPUP_REQUIRED', 'Additional verification is required to make this change.', 403, [], [
                    'required_action' => $requestedIdentityChange ? 'change_identity' : 'change_guardian_contact',
                ]);
            }
            if ($requestedIdentityChange && !AdmissionSessionService::hasStepupForScope($session, 'identity')) {
                return AdmissionResponder::fail('STEPUP_REQUIRED', 'Additional verification is required to make this change.', 403, [], [
                    'required_action' => 'change_identity',
                ]);
            }
            if ($requestedGuardianContactChange && !AdmissionSessionService::hasStepupForScope($session, 'guardian_contact')) {
                return AdmissionResponder::fail('STEPUP_REQUIRED', 'Additional verification is required to make this change.', 403, [], [
                    'required_action' => 'change_guardian_contact',
                ]);
            }
        }

        $updates = [];
        foreach ([
            'application_type',
            'academic_year_id',
            'applying_form_id',
            'preferred_category_id',
            'gender',
            'student_national_id',
            'guardian_name',
            'guardian_national_id',
            'emergency_phone',
            'address',
            'occupation',
            'grade7_school',
            'grade7_results',
            'previous_school_name',
            'current_form',
            'transfer_reason',
            'last_term_average',
            'reason_for_joining',
            'medical_information',
        ] as $field) {
            if ($request->has($field)) {
                $updates[$field] = $data[$field];
            }
        }

        if (array_key_exists('guardian_name', $updates)) $updates['guardian_name'] = AdmissionNormalizer::normalizeName($updates['guardian_name']);
        $rotateToken = false;

        if ($request->has('student_first_name')) {
            $updates['student_first_name'] = AdmissionNormalizer::normalizeName((string) $data['student_first_name']);
            $rotateToken = $rotateToken || ($updates['student_first_name'] !== (string) $app->student_first_name);
        }
        if ($request->has('student_last_name')) {
            $updates['student_last_name'] = AdmissionNormalizer::normalizeName((string) $data['student_last_name']);
            $rotateToken = $rotateToken || ($updates['student_last_name'] !== (string) $app->student_last_name);
        }
        if ($request->has('date_of_birth') && $session && $requestedIdentityChange) {
            $updates['date_of_birth'] = (string) $data['date_of_birth'];
            $rotateToken = true;
        }
        if ($request->has('birth_certificate_number') && $session && $requestedIdentityChange) {
            $norm = AdmissionNormalizer::normalizeBirthCertificate((string) $data['birth_certificate_number']);
            if (!$norm) {
                return AdmissionResponder::fail('INVALID_BIRTH_CERT', 'Birth certificate number is invalid.', 422, ['birth_certificate_number' => ['Invalid value.']]);
            }

            $academicYearIdForDup = (int) ($updates['academic_year_id'] ?? $app->academic_year_id);
            $existingSubmittedId = AdmissionDuplicateService::findSubmittedApplicationId($academicYearIdForDup, $norm);
            if ($existingSubmittedId && $existingSubmittedId !== (int) $app->id) {
                return AdmissionResponder::fail('DUPLICATE_SUBMITTED', 'We cannot accept another submission for this learner for the selected intake year.', 409);
            }
            $existingDraftId = AdmissionDuplicateService::findActiveDraftId($academicYearIdForDup, $norm);
            if ($existingDraftId && $existingDraftId !== (int) $app->id) {
                return AdmissionResponder::fail('DRAFT_EXISTS', 'A draft already exists for this learner and intake year. Please resume with your token or recover access.', 409);
            }

            $updates['birth_certificate_number'] = strtoupper(trim((string) $data['birth_certificate_number']));
            $updates['birth_certificate_number_normalized'] = $norm;
            $rotateToken = true;
        }

        if ($request->has('guardian_email')) {
            $incomingEmail = AdmissionNormalizer::normalizeEmail((string) $data['guardian_email']);
            $updates['guardian_email'] = $incomingEmail;
            if ($storedGuardianEmail && $incomingEmail && $incomingEmail !== $storedGuardianEmail) {
                $rotateToken = true;
            }
        }
        if ($request->has('guardian_phone')) {
            $incomingPhone = trim((string) $data['guardian_phone']);
            $updates['guardian_phone'] = $incomingPhone;
            if ($storedGuardianPhone !== '' && $incomingPhone !== '' && $incomingPhone !== $storedGuardianPhone) {
                $rotateToken = true;
            }
        }

        $now = now();
        $academicYearId = (int) ($updates['academic_year_id'] ?? $app->academic_year_id);
        if (config('admissions.intake.require_open_intake_for_drafts', true)) {
            if (!AdmissionIntakeService::isIntakeOpen($academicYearId)) {
                DB::table('admission_applications')->where('id', $app->id)->update([
                    'archived_at' => $now,
                    'archived_reason' => 'archived_intake_closed',
                    'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.lifecycle.archived_intake_closed', ['academic_year_id' => $academicYearId], 'info', $request);
                return AdmissionResponder::fail('INTAKE_CLOSED', 'This admissions intake is now closed.', 409, [], [
                    'intake_close_at' => AdmissionIntakeService::intakeCloseAt($academicYearId),
                ]);
            }
        }

        $expiresAt = $now->copy()->addDays((int) config('admissions.inactivity_expiry_days', 90));
        $intakeCloseAt = AdmissionIntakeService::intakeCloseAt($academicYearId);
        if ($intakeCloseAt) {
            $close = \Illuminate\Support\Carbon::parse($intakeCloseAt);
            if ($close->lt($expiresAt)) $expiresAt = $close;
        }

        $updates['current_step'] = (int) $data['current_step'];
        $updates['draft_revision'] = $expectedRevision + 1;
        $updates['draft_last_saved_at'] = $now;
        $updates['last_activity_at'] = $now;
        $updates['expires_at'] = $expiresAt;
        $updates['updated_at'] = $now;

        $issuedToken = null;
        $tokenLast4 = null;

        DB::beginTransaction();
        try {
            DB::table('admission_applications')->where('id', $app->id)->update($updates);

            if ($rotateToken) {
                DB::table('admission_application_tokens')
                    ->where('application_id', $app->id)
                    ->where('purpose', 'access')
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => $now, 'updated_at' => $now]);

                $newToken = AdmissionTokenService::generatePlainToken();
                DB::table('admission_application_tokens')->insert([
                    'application_id' => $app->id,
                    'purpose' => 'access',
                    'token_hash' => AdmissionTokenService::hashToken($newToken),
                    'token_last4' => AdmissionTokenService::tokenLast4($newToken),
                    'created_ip' => $request->ip(),
                    'created_ip_hash' => AdmissionSecurityHasher::ipHash($request->ip()),
                    'created_device_hash' => AdmissionSecurityHasher::deviceHash($request),
                    'created_user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 500) : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                $issuedToken = $newToken;
                $tokenLast4 = AdmissionTokenService::tokenLast4($newToken);

                AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.token_rotated', [
                    'reason' => $requestedIdentityChange ? 'identity_change' : 'guardian_contact_change',
                    'token_last4' => $tokenLast4,
                ], 'info', $request);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.draft_save_failed', ['error' => $e->getMessage()], 'critical', $request);
            return AdmissionResponder::fail('SAVE_FAILED', 'Failed to save draft. Please try again.', 500);
        }

        AdmissionAudit::log((int) $app->id, 'applicant', null, 'admissions.v2.draft_saved', [
            'current_step' => (int) $data['current_step'],
            'draft_revision' => $updates['draft_revision'],
        ], 'info', $request);

        return AdmissionResponder::ok([
            'application_id' => (int) $app->id,
            'application_number' => (string) $app->application_number,
            'draft_revision' => (int) $updates['draft_revision'],
            'current_step' => (int) $data['current_step'],
            'expires_at' => $updates['expires_at'],
            'issued_token' => $issuedToken,
            'token_last4' => $tokenLast4,
        ]);
    }

    public function get(Request $request)
    {
        [$app, $session] = AdmissionAccessService::resolveFromRequest($request);
        if (!$app) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Token verification failed.', 403);
        }

        $now = now();
        if ((string) ($app->status ?? '') === 'draft' && empty($app->archived_at)) {
            if (!empty($app->expires_at) && $now->gte($app->expires_at)) {
                DB::table('admission_applications')->where('id', $app->id)->update([
                    'archived_at' => $now,
                    'archived_reason' => 'archived_inactive',
                    'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.lifecycle.archived_inactive', [], 'info', $request);
                $app = DB::table('admission_applications')->where('id', $app->id)->first();
            } elseif (config('admissions.intake.require_open_intake_for_drafts', true) && !AdmissionIntakeService::isIntakeOpen((int) $app->academic_year_id)) {
                DB::table('admission_applications')->where('id', $app->id)->update([
                    'archived_at' => $now,
                    'archived_reason' => 'archived_intake_closed',
                    'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                    'updated_at' => $now,
                ]);
                AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.lifecycle.archived_intake_closed', ['academic_year_id' => (int) $app->academic_year_id], 'info', $request);
                $app = DB::table('admission_applications')->where('id', $app->id)->first();
            }
        }

        $docs = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->whereNull('superseded_at')
            ->orderBy('document_type')
            ->orderByDesc('version')
            ->select('id', 'document_type', 'file_name', 'file_path', 'version', 'uploaded_at')
            ->get();

        return AdmissionResponder::ok([
            'application' => [
                'id' => (int) $app->id,
                'application_number' => (string) $app->application_number,
                'application_type' => (string) $app->application_type,
                'academic_year_id' => $app->academic_year_id ? (int) $app->academic_year_id : null,
                'term_id' => $app->term_id ? (int) $app->term_id : null,
                'applying_form_id' => $app->applying_form_id ? (int) $app->applying_form_id : null,
                'preferred_category_id' => $app->preferred_category_id ? (int) $app->preferred_category_id : null,
                'student_first_name' => $app->student_first_name,
                'student_last_name' => $app->student_last_name,
                'gender' => $app->gender,
                'date_of_birth' => (string) $app->date_of_birth,
                'birth_certificate_number' => $app->birth_certificate_number,
                'student_national_id' => $app->student_national_id,
                'guardian_name' => $app->guardian_name,
                'guardian_national_id' => $app->guardian_national_id,
                'guardian_phone' => $app->guardian_phone,
                'guardian_email' => $app->guardian_email,
                'emergency_phone' => $app->emergency_phone,
                'address' => $app->address,
                'occupation' => $app->occupation,
                'grade7_school' => $app->grade7_school,
                'grade7_results' => $app->grade7_results,
                'previous_school_name' => $app->previous_school_name,
                'current_form' => $app->current_form,
                'transfer_reason' => $app->transfer_reason,
                'last_term_average' => $app->last_term_average,
                'reason_for_joining' => $app->reason_for_joining,
                'medical_information' => $app->medical_information,
                'lifecycle_state' => $app->lifecycle_state,
                'status' => $app->status,
                'current_step' => (int) ($app->current_step ?? 1),
                'draft_revision' => (int) ($app->draft_revision ?? 0),
                'expires_at' => $app->expires_at,
                'archived_at' => $app->archived_at,
                'archived_reason' => $app->archived_reason,
                'locked_at' => $app->locked_at,
            ],
            'documents' => $docs,
        ]);
    }

    private function generateApplicationNumber(): string
    {
        $year = date('Y');
        $count = DB::table('admission_applications')->whereYear('created_at', $year)->lockForUpdate()->count() + 1;
        $number = 'APP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        while (DB::table('admission_applications')->where('application_number', $number)->exists()) {
            $count++;
            $number = 'APP-' . $year . '-' . str_pad($count, 5, '0', STR_PAD_LEFT);
        }
        return $number;
    }
}
