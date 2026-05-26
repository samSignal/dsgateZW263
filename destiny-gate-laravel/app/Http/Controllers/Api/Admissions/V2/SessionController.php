<?php

namespace App\Http\Controllers\Api\Admissions\V2;

use App\Http\Controllers\Controller;
use App\Support\Admissions\AdmissionAccessService;
use App\Support\Admissions\AdmissionAudit;
use App\Support\Admissions\AdmissionResponder;
use App\Support\Admissions\AdmissionSecurityHasher;
use App\Support\Admissions\AdmissionSecurityService;
use App\Support\Admissions\AdmissionSessionService;
use App\Support\Admissions\AdmissionTokenService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SessionController extends Controller
{
    public function start(Request $request)
    {
        $ipHash = AdmissionSecurityHasher::ipHash($request->ip()) ?? 'noip';
        $tokenHashKey = AdmissionTokenService::hashToken((string) $request->input('token', ''));

        $threshold = (int) config('admissions.suspicious.token_verify_fail_threshold', 5);
        $lockout = (int) config('admissions.suspicious.lockout_minutes', 15);

        if (AdmissionSecurityService::isLocked('token_verify_fail_ip', $ipHash)) {
            AdmissionAudit::log(null, 'system', null, 'admissions.security.lockout_blocked', ['type' => 'token_verify_fail_ip'], 'warning', $request);
            return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
        }

        $data = AdmissionAccessService::requestBasic($request);
        $app = AdmissionAccessService::resolveByTokenAndDob($data['token'], $data['date_of_birth']);
        if (!$app) {
            $ipRes = null;
            $tokenRes = null;
            DB::transaction(function () use ($ipHash, $tokenHashKey, $threshold, $lockout, &$ipRes, &$tokenRes) {
                $ipRes = AdmissionSecurityService::recordFailure('token_verify_fail_ip', $ipHash, $threshold, $lockout);
                $tokenRes = AdmissionSecurityService::recordFailure('token_verify_fail_token', $tokenHashKey, $threshold, $lockout);
            });

            AdmissionAudit::log(null, 'system', null, 'admissions.v2.session_start_failed', [], 'warning', $request);
            if (!empty($ipRes['locked_until']) || !empty($tokenRes['locked_until'])) {
                AdmissionAudit::log(null, 'system', null, 'admissions.security.lockout_applied', ['type' => 'token_verify_fail_ip'], 'warning', $request);
                return AdmissionResponder::fail('LOCKED', 'Too many attempts. Please try again later.', 429);
            }
            return AdmissionResponder::fail('UNAUTHORIZED', 'Token verification failed.', 403);
        }

        DB::transaction(function () use ($ipHash, $tokenHashKey) {
            AdmissionSecurityService::reset('token_verify_fail_ip', $ipHash);
            AdmissionSecurityService::reset('token_verify_fail_token', $tokenHashKey);
        });

        $issued = AdmissionSessionService::issue((int) $app->id, $request);
        return AdmissionResponder::ok([
            'application_id' => (int) $app->id,
            'application_number' => (string) $app->application_number,
            'session_token' => $issued['session_token'],
            'expires_at' => (string) $issued['expires_at'],
            'stepup_until' => null,
        ]);
    }
}
