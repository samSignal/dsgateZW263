<?php

namespace App\Support\Admissions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdmissionSessionService
{
    public static function issue(int $applicationId, Request $request): array
    {
        $plain = 'AS-' . Str::upper(Str::random(24));
        $hash = hash_hmac('sha256', $plain, (string) config('app.key'));
        $now = now();

        $ttl = (int) config('admissions.session_ttl_minutes', 120);
        $expiresAt = $now->copy()->addMinutes($ttl);

        $deviceHash = AdmissionSecurityHasher::deviceHash($request);
        $ipHash = AdmissionSecurityHasher::ipHash($request->ip());

        DB::table('admission_sessions')->insert([
            'application_id' => $applicationId,
            'session_hash' => $hash,
            'session_last4' => substr($plain, -4),
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
            'expires_at' => $expiresAt,
            'stepup_until' => null,
            'stepup_scopes' => null,
            'last_seen_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        AdmissionAudit::log($applicationId, 'system', null, 'admissions.v2.session_issued', [
            'expires_at' => (string) $expiresAt,
        ], 'info', $request);

        return [
            'session_token' => $plain,
            'expires_at' => $expiresAt,
            'stepup_until' => null,
        ];
    }

    public static function resolve(Request $request): ?object
    {
        $token = (string) $request->header('x-admissions-session', '');
        if ($token === '' || strlen($token) > 120) return null;
        $hash = hash_hmac('sha256', strtoupper($token), (string) config('app.key'));
        $now = now();

        $row = DB::table('admission_sessions')
            ->where('session_hash', $hash)
            ->whereNull('revoked_at')
            ->first();
        if (!$row) return null;

        if ($row->expires_at && $now->gte($row->expires_at)) {
            DB::table('admission_sessions')->where('id', $row->id)->update(['revoked_at' => $now, 'updated_at' => $now]);
            AdmissionAudit::log((int) $row->application_id, 'system', null, 'admissions.v2.session_expired', [], 'info', $request);
            return null;
        }

        $idle = (int) config('admissions.session_idle_minutes', 30);
        if ($row->last_seen_at && $now->diffInMinutes($row->last_seen_at) >= $idle) {
            DB::table('admission_sessions')->where('id', $row->id)->update(['revoked_at' => $now, 'updated_at' => $now]);
            AdmissionAudit::log((int) $row->application_id, 'system', null, 'admissions.v2.session_idle_timeout', [], 'info', $request);
            return null;
        }

        $ipHash = AdmissionSecurityHasher::ipHash($request->ip());
        if ($row->ip_hash && $ipHash && $row->ip_hash !== $ipHash) {
            AdmissionAudit::log((int) $row->application_id, 'system', null, 'admissions.v2.session_ip_mismatch', [], 'warning', $request);
            if (config('admissions.session_bind_ip', false)) {
                DB::table('admission_sessions')->where('id', $row->id)->update(['revoked_at' => $now, 'updated_at' => $now]);
                return null;
            }
        }

        $deviceHash = AdmissionSecurityHasher::deviceHash($request);
        if ($row->device_hash && $row->device_hash !== $deviceHash) {
            AdmissionAudit::log((int) $row->application_id, 'system', null, 'admissions.v2.session_device_mismatch', [], 'warning', $request);
            DB::table('admission_sessions')->where('id', $row->id)->update(['revoked_at' => $now, 'updated_at' => $now]);
            return null;
        }

        DB::table('admission_sessions')->where('id', $row->id)->update(['last_seen_at' => $now, 'updated_at' => $now]);
        return $row;
    }

    public static function hasStepup(object $session): bool
    {
        if (!$session->stepup_until) return false;
        return now()->lt($session->stepup_until);
    }

    public static function hasStepupForScope(object $session, string $scope): bool
    {
        if (!self::hasStepup($session)) return false;
        $raw = $session->stepup_scopes ?? null;
        if ($raw === null) return true;

        $scopes = is_array($raw) ? $raw : json_decode((string) $raw, true);
        if (!is_array($scopes)) return false;
        if (in_array('*', $scopes, true)) return true;
        return in_array($scope, $scopes, true);
    }

    public static function upgradeStepup(int $sessionId, Request $request, array $scopes = ['*']): object
    {
        $now = now();
        $ttl = (int) config('admissions.stepup_ttl_minutes', 10);
        $until = $now->copy()->addMinutes($ttl);

        $scopes = array_values(array_unique(array_map('strval', $scopes)));
        DB::table('admission_sessions')->where('id', $sessionId)->update([
            'stepup_until' => $until,
            'stepup_scopes' => json_encode($scopes),
            'updated_at' => $now,
        ]);

        $row = DB::table('admission_sessions')->where('id', $sessionId)->first();

        AdmissionAudit::log((int) $row->application_id, 'system', null, 'admissions.v2.session_stepup_granted', [
            'stepup_until' => (string) $until,
            'stepup_scopes' => $scopes,
        ], 'info', $request);

        return $row;
    }
}
