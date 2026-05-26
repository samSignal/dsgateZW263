<?php

namespace App\Support\Admissions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdmissionChallengeService
{
    public static function create(int $applicationId, ?int $sessionId, string $verificationType, string $action, ?string $emailTo, Request $request, array $meta = []): array
    {
        $plain = 'CH-' . Str::upper(Str::random(32));
        $hash = AdmissionTokenService::hashToken($plain);
        $now = now();
        $expiresAt = $now->copy()->addMinutes((int) config('admissions.magic_link_expiry_minutes', 20));

        DB::table('admission_verification_challenges')
            ->where('application_id', $applicationId)
            ->where('verification_type', $verificationType)
            ->where('action', $action)
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->update(['revoked_at' => $now, 'updated_at' => $now]);

        $id = DB::table('admission_verification_challenges')->insertGetId([
            'application_id' => $applicationId,
            'session_id' => $sessionId,
            'verification_type' => $verificationType,
            'action' => $action,
            'email_to' => $emailTo,
            'token_hash' => $hash,
            'token_last4' => AdmissionTokenService::tokenLast4($plain),
            'expires_at' => $expiresAt,
            'requested_ip_hash' => AdmissionSecurityHasher::ipHash($request->ip()),
            'requested_device_hash' => AdmissionSecurityHasher::deviceHash($request),
            'correlation_id' => AdmissionSecurityHasher::correlationId($request),
            'meta' => empty($meta) ? null : json_encode($meta),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        AdmissionAudit::log($applicationId, 'system', null, 'admissions.v2.challenge_created', [
            'challenge_id' => $id,
            'verification_type' => $verificationType,
            'action' => $action,
            'expires_at' => (string) $expiresAt,
        ], 'info', $request);

        return [
            'challenge_id' => $id,
            'plain_token' => $plain,
            'expires_at' => $expiresAt,
        ];
    }

    public static function consume(string $plainToken, Request $request): ?object
    {
        $hash = AdmissionTokenService::hashToken($plainToken);
        $now = now();

        return DB::transaction(function () use ($hash, $now, $request) {
            $row = DB::table('admission_verification_challenges')
                ->where('token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if (!$row) return null;
            if ($row->revoked_at) return null;
            if ($row->used_at) return null;
            if ($row->expires_at && $now->gte($row->expires_at)) return null;

            $updated = DB::table('admission_verification_challenges')
                ->where('id', $row->id)
                ->whereNull('used_at')
                ->whereNull('revoked_at')
                ->update([
                    'used_at' => $now,
                    'updated_at' => $now,
                ]);

            if ($updated !== 1) return null;

            AdmissionAudit::log($row->application_id ? (int) $row->application_id : null, 'system', null, 'admissions.v2.challenge_consumed', [
                'challenge_id' => (int) $row->id,
                'verification_type' => $row->verification_type,
                'action' => $row->action,
            ], 'info', $request);

            return $row;
        });
    }
}
