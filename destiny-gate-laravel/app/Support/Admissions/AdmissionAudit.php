<?php

namespace App\Support\Admissions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionAudit
{
    public static function log(?int $applicationId, string $actorType, ?int $actorUserId, string $eventType, array $meta = [], string $severity = 'info', ?Request $request = null): void
    {
        $ip = $request?->ip();
        $ua = $request?->userAgent();
        $correlationId = $request ? AdmissionSecurityHasher::correlationId($request) : null;
        $ipHash = AdmissionSecurityHasher::ipHash($ip);
        $deviceHash = $request ? AdmissionSecurityHasher::deviceHash($request) : null;

        DB::table('admission_audit_events')->insert([
            'application_id' => $applicationId,
            'actor_type' => $actorType,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'severity' => $severity,
            'correlation_id' => $correlationId,
            'ip' => null,
            'ip_hash' => $ipHash,
            'device_hash' => $deviceHash,
            'user_agent' => $ua ? substr($ua, 0, 500) : null,
            'meta' => empty($meta) ? null : json_encode($meta),
            'created_at' => now(),
        ]);
    }
}
