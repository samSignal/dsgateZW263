<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Mail\AdmissionsMagicLinkMail;
use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionChallengeService;
use App\Support\Admissions\AdmissionDuplicateService;
use App\Support\Admissions\AdmissionNormalizer;
use App\Support\Admissions\AdmissionResponder;
use App\Support\Admissions\AdmissionSecurityHasher;
use App\Support\Admissions\AdmissionSecurityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RecoveryController extends Controller
{
    public function request(Request $request)
    {
        $data = $request->validate([
            'academic_year_id' => 'required|integer|exists:academic_years,id',
            'birth_certificate_number' => 'required|string|max:80',
            'guardian_email' => 'required|email|max:320',
        ]);

        $ipHash = AdmissionSecurityHasher::ipHash($request->ip()) ?? 'noip';
        $threshold = (int) config('admissions.suspicious.recovery_request_threshold', 5);
        $lockout = (int) config('admissions.suspicious.lockout_minutes', 15);
        if (AdmissionSecurityService::isLocked('recovery_request_ip', $ipHash)) {
            AdmissionAudit::log(null, 'system', null, 'admissions.security.lockout_blocked', ['type' => 'recovery_request_ip'], 'warning', $request);
            return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
        }

        $result = AdmissionSecurityService::recordFailure('recovery_request_ip', $ipHash, $threshold, $lockout);
        if (!empty($result['locked_until'])) {
            AdmissionAudit::log(null, 'system', null, 'admissions.security.lockout_applied', ['type' => 'recovery_request_ip'], 'warning', $request);
            return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
        }

        $birthNorm = AdmissionNormalizer::normalizeBirthCertificate($data['birth_certificate_number']);
        if (!$birthNorm) {
            return AdmissionResponder::ok([
                'message' => 'If we find a matching application, we will send recovery instructions to the contact details provided.',
            ]);
        }

        $academicYearId = (int) $data['academic_year_id'];
        $applicationId = AdmissionDuplicateService::findSubmittedApplicationId($academicYearId, $birthNorm)
            ?? AdmissionDuplicateService::findActiveDraftId($academicYearId, $birthNorm);

        AdmissionAudit::log($applicationId, 'system', null, 'admissions.v2.recovery_requested', [
            'academic_year_id' => $academicYearId,
            'birth_certificate_hash' => AdmissionSecurityHasher::keyHash('bc:' . $birthNorm),
        ], 'info', $request);

        if (!$applicationId) {
            return AdmissionResponder::ok([
                'message' => 'If we find a matching application, we will send recovery instructions to the contact details provided.',
            ]);
        }

        $app = DB::table('admission_applications')->where('id', $applicationId)->first();
        if (!$app) {
            return AdmissionResponder::ok([
                'message' => 'If we find a matching application, we will send recovery instructions to the contact details provided.',
            ]);
        }

        $targetEmail = strtolower(trim((string) $data['guardian_email']));
        $storedEmail = strtolower(trim((string) ($app->guardian_email ?? '')));
        if ($storedEmail === '' || $storedEmail !== $targetEmail) {
            return AdmissionResponder::ok([
                'message' => 'If we find a matching application, we will send recovery instructions to the contact details provided.',
            ]);
        }

        $challenge = AdmissionChallengeService::create((int) $applicationId, null, 'recovery', 'recover_access', $targetEmail, $request);
        AdmissionSecurityService::reset('recovery_request_ip', $ipHash);
        $publicUrl = rtrim((string) config('admissions.public_urls.recovery'), '/');
        $link = $publicUrl . '?code=' . urlencode($challenge['plain_token']);
        $expiresText = 'This link expires in ' . (int) config('admissions.magic_link_expiry_minutes', 20) . ' minutes and can only be used once.';

        try {
            Mail::to($targetEmail)->send(new AdmissionsMagicLinkMail(
                'Admissions recovery link',
                'Use the secure link below to recover access to your admissions application. No personal information is included in this email.',
                'Recover application access',
                $link,
                $expiresText
            ));
        } catch (\Throwable $e) {
        }

        DB::table('application_notifications')->insert([
            'application_id' => $applicationId,
            'title' => 'Recovery Requested',
            'message' => 'Recovery link requested. If the email matches our records, a secure link has been sent.',
            'notification_channel' => 'email',
            'notification_type' => 'info',
            'sent_by' => null,
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return AdmissionResponder::ok([
            'message' => 'If we find a matching application, we will send recovery instructions to the contact details provided.',
        ]);
    }
}
