<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Central write path for the audit_logs table — every entry, whether from the generic
 * request-logging middleware or an explicit call (login/logout), goes through here so the
 * row shape stays consistent.
 */
class AuditLogger
{
    public static function log(
        ?int $userId,
        ?string $userName,
        ?string $userRole,
        string $action,
        string $description,
        ?Request $request = null,
        ?int $statusCode = null
    ): void {
        DB::table('audit_logs')->insert([
            'user_id'     => $userId,
            'user_name'   => $userName,
            'user_role'   => $userRole,
            'action'      => $action,
            'description' => $description,
            'method'      => $request?->method(),
            'path'        => $request ? '/' . ltrim($request->path(), '/') : null,
            'status_code' => $statusCode,
            'ip_address'  => $request?->ip(),
            'user_agent'  => $request ? substr((string) $request->userAgent(), 0, 255) : null,
            'created_at'  => now(),
        ]);
    }
}
