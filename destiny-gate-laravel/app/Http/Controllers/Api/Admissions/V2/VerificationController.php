<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Mail\AdmissionsMagicLinkMail;
use App\Support\Admissions\AdmissionAccessService;
use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionChallengeService;
use App\Support\Admissions\AdmissionIntakeService;
use App\Support\Admissions\AdmissionResponder;
use App\Support\Admissions\AdmissionSecurityHasher;
use App\Support\Admissions\AdmissionSecurityService;
use App\Support\Admissions\AdmissionSessionService;
use App\Support\Admissions\AdmissionTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class VerificationController extends Controller
{
    public function requestStepup(Request $request)
    {
        [$app, $session] = AdmissionAccessService::resolveFromRequest($request);
        if (!$app || !$session) {
            return AdmissionResponder::fail('UNAUTHORIZED', 'Session verification failed.', 403);
        }

        $actionInput = $request->input('action');
        $action = is_string($actionInput) ? trim($actionInput) : '';
        if ($action === '') $action = 'sensitive_action';
        $allowed = (array) config('admissions.stepup.allowed_actions', ['sensitive_action']);
        if (!in_array($action, $allowed, true)) $action = 'sensitive_action';

        $ipHash = AdmissionSecurityHasher::ipHash($request->ip()) ?? 'noip';
        $threshold = (int) config('admissions.suspicious.stepup_request_threshold', 10);
        $lockout = (int) config('admissions.suspicious.lockout_minutes', 15);
        if (AdmissionSecurityService::isLocked('stepup_request_ip', $ipHash)) {
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.lockout_blocked', ['type' => 'stepup_request_ip'], 'warning', $request);
            return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
        }
        $r = AdmissionSecurityService::recordFailure('stepup_request_ip', $ipHash, $threshold, $lockout);
        if (!empty($r['locked_until'])) {
            AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.security.lockout_applied', ['type' => 'stepup_request_ip'], 'warning', $request);
            return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
        }

        $email = (string) ($app->guardian_email ?? '');
        if ($email === '') {
            return AdmissionResponder::ok(['message' => 'If your email matches our records, we will send a verification link.']);
        }

        $challenge = AdmissionChallengeService::create((int) $app->id, (int) $session->id, 'stepup', $action, $email, $request, [
            'requested' => 'stepup',
            'action' => $action,
        ]);

        $publicUrl = rtrim((string) config('admissions.public_urls.verify'), '/');
        $link = $publicUrl . '?code=' . urlencode($challenge['plain_token']);
        $expiresText = 'This link expires in ' . (int) config('admissions.magic_link_expiry_minutes', 20) . ' minutes and can only be used once.';

        try {
            Mail::to($email)->send(new AdmissionsMagicLinkMail(
                'Verify to continue',
                'For your security, we need to verify your request before allowing this change.',
                'Verify and continue',
                $link,
                $expiresText
            ));
        } catch (\Throwable $e) {
        }

        AdmissionAudit::log((int) $app->id, 'system', null, 'admissions.v2.stepup_requested', ['action' => $action], 'info', $request);

        return AdmissionResponder::ok(['message' => 'If your email matches our records, we will send a verification link.']);
    }

    public function consume(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|max:120',
        ]);

        $ipHash = AdmissionSecurityHasher::ipHash($request->ip()) ?? 'noip';
        $threshold = (int) config('admissions.suspicious.verification_consume_fail_threshold', 10);
        $lockout = (int) config('admissions.suspicious.lockout_minutes', 15);
        if (AdmissionSecurityService::isLocked('verification_consume_fail_ip', $ipHash)) {
            AdmissionAudit::log(null, 'system', null, 'admissions.security.lockout_blocked', ['type' => 'verification_consume_fail_ip'], 'warning', $request);
            return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
        }

        $row = AdmissionChallengeService::consume($data['code'], $request);
        if (!$row) {
            $r = AdmissionSecurityService::recordFailure('verification_consume_fail_ip', $ipHash, $threshold, $lockout);
            AdmissionAudit::log(null, 'system', null, 'admissions.v2.challenge_invalid', [], 'warning', $request);
            if (!empty($r['locked_until'])) {
                AdmissionAudit::log(null, 'system', null, 'admissions.security.lockout_applied', ['type' => 'verification_consume_fail_ip'], 'warning', $request);
                return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
            }
            return AdmissionResponder::fail('INVALID_OR_EXPIRED', 'This verification link is invalid or has expired.', 410);
        }

        $applicationId = $row->application_id ? (int) $row->application_id : null;
        if (!$applicationId) {
            return AdmissionResponder::fail('INVALID_OR_EXPIRED', 'This verification link is invalid or has expired.', 410);
        }

        $app = DB::table('admission_applications')->where('id', $applicationId)->first();
        if (!$app) {
            return AdmissionResponder::fail('INVALID_OR_EXPIRED', 'This verification link is invalid or has expired.', 410);
        }

        if ($row->verification_type === 'stepup') {
            $sessionId = $row->session_id ? (int) $row->session_id : null;
            if ($sessionId) {
                if ($row->requested_device_hash && $row->requested_device_hash !== AdmissionSecurityHasher::deviceHash($request)) {
                    AdmissionAudit::log($applicationId, 'system', null, 'admissions.v2.stepup_device_mismatch', [], 'warning', $request);
                    return AdmissionResponder::fail('INVALID_OR_EXPIRED', 'This verification link is invalid or has expired.', 410);
                }

                $sessionRow = DB::table('admission_sessions')
                    ->where('id', $sessionId)
                    ->where('application_id', $applicationId)
                    ->whereNull('revoked_at')
                    ->first();
                if (!$sessionRow || ($sessionRow->expires_at && now()->gte($sessionRow->expires_at))) {
                    return AdmissionResponder::fail('INVALID_OR_EXPIRED', 'This verification link is invalid or has expired.', 410);
                }

                $scopes = (array) data_get(config('admissions.stepup.action_scopes', []), (string) $row->action, ['*']);
                $upgraded = AdmissionSessionService::upgradeStepup($sessionId, $request, $scopes);
                AdmissionSecurityService::reset('verification_consume_fail_ip', $ipHash);
                return AdmissionResponder::ok([
                    'type' => 'stepup',
                    'application_id' => $applicationId,
                    'application_number' => (string) $app->application_number,
                    'session_stepup_until' => (string) $upgraded->stepup_until,
                    'session_stepup_scopes' => $scopes,
                ]);
            }

            return AdmissionResponder::fail('UNAUTHORIZED', 'Session verification failed.', 403);
        }

        if ($row->verification_type === 'recovery') {
            $now = now();
            $issuedAccessToken = null;
            $tokenLast4 = null;

            DB::beginTransaction();
            try {
                DB::table('admission_application_tokens')
                    ->where('application_id', $applicationId)
                    ->where('purpose', 'access')
                    ->whereNull('revoked_at')
                    ->update(['revoked_at' => $now, 'updated_at' => $now]);

                AdmissionAudit::log($applicationId, 'system', null, 'admissions.security.token_revoked', [
                    'reason' => 'recovery',
                ], 'info', $request);

                $newToken = AdmissionTokenService::generatePlainToken();
                DB::table('admission_application_tokens')->insert([
                    'application_id' => $applicationId,
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

                $issuedAccessToken = $newToken;
                $tokenLast4 = AdmissionTokenService::tokenLast4($newToken);

                AdmissionAudit::log($applicationId, 'system', null, 'admissions.security.token_rotated', [
                    'reason' => 'recovery',
                    'token_last4' => $tokenLast4,
                ], 'info', $request);

                $state = (string) ($app->lifecycle_state ?? '');
                $status = (string) ($app->status ?? '');
                $isDraft = $status === 'draft' || in_array($state, ['DRAFT_ACTIVE', 'DRAFT_EXPIRED_ARCHIVED', 'DRAFT_REACTIVATED'], true);

                if ($isDraft && !empty($app->archived_at)) {
                    $canReopen = true;
                    if (config('admissions.intake.require_open_intake_for_drafts', true)) {
                        if (!AdmissionIntakeService::isIntakeOpen((int) $app->academic_year_id)) {
                            $canReopen = false;
                        }
                    }

                    if ($canReopen) {
                        $expiresAt = now()->copy()->addDays((int) config('admissions.inactivity_expiry_days', 90));
                        DB::table('admission_applications')->where('id', $applicationId)->update([
                            'archived_at' => null,
                            'archived_reason' => null,
                            'lifecycle_state' => 'DRAFT_REACTIVATED',
                            'expires_at' => $expiresAt,
                            'last_activity_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
                }

                AdmissionAudit::log($applicationId, 'system', null, 'admissions.v2.recovery_completed', [
                    'token_last4' => $tokenLast4,
                ], 'info', $request);

                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                AdmissionAudit::log($applicationId, 'system', null, 'admissions.v2.recovery_failed', ['error' => $e->getMessage()], 'critical', $request);
                return AdmissionResponder::fail('RECOVERY_FAILED', 'Recovery failed. Please try again later.', 500);
            }

            $sessionIssued = AdmissionSessionService::issue($applicationId, $request);

            AdmissionSecurityService::reset('verification_consume_fail_ip', $ipHash);
            return AdmissionResponder::ok([
                'type' => 'recovery',
                'application_id' => $applicationId,
                'application_number' => (string) $app->application_number,
                'issued_token' => $issuedAccessToken,
                'token_last4' => $tokenLast4,
                'session_token' => $sessionIssued['session_token'],
                'session_expires_at' => (string) $sessionIssued['expires_at'],
            ]);
        }

        return AdmissionResponder::fail('INVALID_OR_EXPIRED', 'This verification link is invalid or has expired.', 410);
    }
}
