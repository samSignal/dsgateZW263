<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionAccessService;
use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionDuplicateService;
use App\Support\Admissions\AdmissionIntakeService;
use App\Support\Admissions\AdmissionNormalizer;
use App\Support\Admissions\AdmissionRequirements;
use App\Support\Admissions\AdmissionResponder;
use App\Support\Admissions\AdmissionSecurityHasher;
use App\Support\Admissions\AdmissionTokenService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubmitController extends Controller
{
    public function submit(Request $request)
    {
        [$app, $session] = AdmissionAccessService::resolveFromRequest($request);
        if (!$app) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Token verification failed.', 403);
        }
        if (!empty($app->archived_at)) {
            return AdmissionResponder::fail('DRAFT_ARCHIVED', 'This draft is archived and cannot be submitted online.', 409);
        }
        if (in_array((string) ($app->lifecycle_state ?? ''), ['DUPLICATE_INVALID', 'ARCHIVED'], true)) {
            return AdmissionResponder::fail('NOT_SUBMITTABLE', 'This application cannot be submitted in its current state.', 409);
        }
        if (config('admissions.intake.require_open_intake_for_drafts', true)) {
            if (!AdmissionIntakeService::isIntakeOpen((int) $app->academic_year_id)) {
                return AdmissionResponder::fail('INTAKE_CLOSED', 'This admissions intake is now closed.', 409, [], [
                    'intake_close_at' => AdmissionIntakeService::intakeCloseAt((int) $app->academic_year_id),
                ]);
            }
        }
        if (!empty($app->is_submitted) || in_array((string) ($app->lifecycle_state ?? ''), ['SUBMITTED', 'UNDER_REVIEW', 'DOCUMENTS_REQUIRED'], true)) {
            return AdmissionResponder::fail('ALREADY_SUBMITTED', 'This application has already been submitted.', 409);
        }

        $idempotencyKey = (string) $request->header('X-Idempotency-Key', '');
        if ($idempotencyKey === '' || strlen($idempotencyKey) > 120) {
            return AdmissionResponder::fail('IDEMPOTENCY_REQUIRED', 'X-Idempotency-Key header is required.', 422);
        }

        $errors = $this->validateStoredApplication($app);
        if (!empty($errors)) {
            return AdmissionResponder::fail('VALIDATION_FAILED', 'Please complete the required fields before submission.', 422, $errors);
        }

        $requiredDocs = AdmissionRequirements::requiredDocuments((string) $app->application_type);
        $presentDocs = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->whereNull('superseded_at')
            ->pluck('document_type')
            ->all();

        $missingDocs = array_values(array_diff($requiredDocs, $presentDocs));
        if (!empty($missingDocs)) {
            return AdmissionResponder::fail('MISSING_DOCUMENTS', 'Please upload all required documents before submission.', 422, [
                'documents' => array_map(fn ($d) => "Missing: {$d}", $missingDocs),
            ], ['required_documents' => $requiredDocs, 'missing_documents' => $missingDocs]);
        }

        $birthNorm = AdmissionNormalizer::normalizeBirthCertificate($app->birth_certificate_number ?? null);
        if (!$birthNorm) {
            return AdmissionResponder::fail('INVALID_BIRTH_CERT', 'Birth certificate number is invalid.', 422, ['birth_certificate_number' => ['Invalid value.']]);
        }

        $existingSubmittedId = AdmissionDuplicateService::findSubmittedApplicationId((int) $app->academic_year_id, $birthNorm);
        if ($existingSubmittedId) {
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.duplicate_submitted_block', [
                'academic_year_id' => (int) $app->academic_year_id,
                'birth_certificate_hash' => AdmissionSecurityHasher::keyHash('bc:' . $birthNorm),
                'existing_application_id' => $existingSubmittedId,
            ], 'warning', $request);

            return AdmissionResponder::fail(
                'DUPLICATE_SUBMITTED',
                'We cannot accept another submission for this learner for the selected intake year.',
                409,
                [],
                ['recovery' => ['track_requires' => ['token', 'date_of_birth'], 'recover_access' => true]]
            );
        }

        $existingIdem = DB::table('admission_application_submissions')
            ->where('application_id', $app->id)
            ->where('idempotency_key', $idempotencyKey)
            ->first();
        if ($existingIdem) {
            return AdmissionResponder::ok([
                'application_id' => (int) $app->id,
                'application_number' => (string) $app->application_number,
                'submission_id' => (int) $existingIdem->id,
                'submitted_at' => $existingIdem->submitted_at,
                'token_last4' => null,
            ], ['idempotent_replay' => true]);
        }

        DB::beginTransaction();
        try {
            $payload = $this->submissionSnapshot($app, $requiredDocs);

            $submissionId = DB::table('admission_application_submissions')->insertGetId([
                'application_id' => $app->id,
                'intake_academic_year_id' => (int) $app->academic_year_id,
                'birth_certificate_number_normalized' => $birthNorm,
                'idempotency_key' => $idempotencyKey,
                'payload' => json_encode($payload),
                'submitted_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $now = now();
            DB::table('admission_applications')->where('id', $app->id)->update([
                'status' => 'submitted',
                'lifecycle_state' => 'SUBMITTED',
                'is_submitted' => true,
                'submitted_at' => $now,
                'status_updated_at' => $now,
                'locked_at' => $now,
                'draft_revision' => ((int) ($app->draft_revision ?? 0)) + 1,
                'updated_at' => $now,
            ]);

            DB::table('admission_application_tokens')
                ->where('application_id', $app->id)
                ->where('purpose', 'access')
                ->whereNull('revoked_at')
                ->update(['revoked_at' => $now, 'updated_at' => $now]);

            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.token_revoked', [
                'reason' => 'submitted',
            ], 'info', $request);

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

            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.token_rotated', [
                'reason' => 'submitted',
                'token_last4' => AdmissionTokenService::tokenLast4($newToken),
            ], 'info', $request);

            AdmissionAudit::log((int) $app->id, 'applicant', null, 'admissions.v2.submitted', [
                'submission_id' => $submissionId,
                'intake_academic_year_id' => (int) $app->academic_year_id,
            ], 'info', $request);

            DB::commit();

            return AdmissionResponder::ok([
                'application_id' => (int) $app->id,
                'application_number' => (string) $app->application_number,
                'submission_id' => (int) $submissionId,
                'submitted_at' => $now,
                'issued_token' => $newToken,
                'token_last4' => AdmissionTokenService::tokenLast4($newToken),
                'lifecycle_state' => 'SUBMITTED',
            ]);
        } catch (QueryException $e) {
            DB::rollBack();

            $msg = strtolower($e->getMessage());
            if (str_contains($msg, 'aas_year_birthcert_unique') || str_contains($msg, 'unique') || str_contains($msg, 'duplicate')) {
                AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.duplicate_submitted_race', [
                    'academic_year_id' => (int) $app->academic_year_id,
                    'birth_certificate_hash' => AdmissionSecurityHasher::keyHash('bc:' . $birthNorm),
                ], 'warning', $request);

                return AdmissionResponder::fail('DUPLICATE_SUBMITTED', 'We cannot accept another submission for this learner for the selected intake year.', 409);
            }

            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.submit_failed', ['error' => $e->getMessage()], 'critical', $request);
            return AdmissionResponder::fail('SUBMIT_FAILED', 'Failed to submit application. Please try again.', 500);
        } catch (\Throwable $e) {
            DB::rollBack();
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.submit_failed', ['error' => $e->getMessage()], 'critical', $request);
            return AdmissionResponder::fail('SUBMIT_FAILED', 'Failed to submit application. Please try again.', 500);
        }
    }

    private function validateStoredApplication(object $app): array
    {
        $errors = [];
        $required = [
            'application_type',
            'academic_year_id',
            'applying_form_id',
            'preferred_category_id',
            'student_first_name',
            'student_last_name',
            'gender',
            'date_of_birth',
            'birth_certificate_number',
            'guardian_name',
            'guardian_phone',
            'guardian_email',
            'emergency_phone',
        ];

        foreach ($required as $field) {
            if (empty($app->{$field})) {
                $errors[$field] = ['Required.'];
            }
        }

        if ((string) $app->application_type === 'transfer') {
            foreach (['previous_school_name', 'current_form', 'transfer_reason'] as $field) {
                if (empty($app->{$field})) {
                    $errors[$field] = ['Required for transfer applications.'];
                }
            }
        } else {
            foreach (['grade7_school', 'grade7_results'] as $field) {
                if (empty($app->{$field})) {
                    $errors[$field] = ['Required for new intake applications.'];
                }
            }
        }

        return $errors;
    }

    private function submissionSnapshot(object $app, array $requiredDocs): array
    {
        $docs = DB::table('application_documents')
            ->where('application_id', $app->id)
            ->whereNull('superseded_at')
            ->orderBy('document_type')
            ->orderByDesc('version')
            ->select('document_type', 'file_name', 'file_path', 'version', 'uploaded_at')
            ->get();

        return [
            'application' => [
                'application_number' => (string) $app->application_number,
                'application_type' => (string) $app->application_type,
                'academic_year_id' => (int) $app->academic_year_id,
                'applying_form_id' => (int) $app->applying_form_id,
                'preferred_category_id' => (int) $app->preferred_category_id,
                'student_first_name' => (string) ($app->student_first_name ?? ''),
                'student_last_name' => (string) ($app->student_last_name ?? ''),
                'gender' => $app->gender,
                'date_of_birth' => (string) $app->date_of_birth,
                'birth_certificate_number' => (string) ($app->birth_certificate_number ?? ''),
                'student_national_id' => $app->student_national_id,
                'guardian_name' => (string) ($app->guardian_name ?? ''),
                'guardian_national_id' => $app->guardian_national_id,
                'guardian_phone' => (string) ($app->guardian_phone ?? ''),
                'guardian_email' => (string) ($app->guardian_email ?? ''),
                'emergency_phone' => (string) ($app->emergency_phone ?? ''),
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
            ],
            'documents' => $docs,
            'required_documents' => $requiredDocs,
        ];
    }
}
