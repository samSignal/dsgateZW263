<?php

namespace App\Support\Admissions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdmissionAccessService
{
    public static function resolveFromRequest(Request $request): array
    {
        $session = AdmissionSessionService::resolve($request);
        if ($session) {
            $app = DB::table('admission_applications')->where('id', $session->application_id)->first();
            return [$app, $session];
        }

        $data = self::requestBasic($request);
        $app = self::resolveByTokenAndDob($data['token'], $data['date_of_birth']);
        return [$app, null];
    }

    public static function resolveByTokenAndDob(string $token, string $dateOfBirth): ?object
    {
        $token = strtoupper(trim($token));
        $hash = AdmissionTokenService::hashToken($token);

        $app = DB::table('admission_application_tokens as t')
            ->join('admission_applications as aa', 't.application_id', '=', 'aa.id')
            ->whereNull('t.revoked_at')
            ->where('t.purpose', 'access')
            ->where('t.token_hash', $hash)
            ->select('aa.*')
            ->first();

        if ($app && (string) $app->date_of_birth === $dateOfBirth) {
            return $app;
        }

        $legacy = DB::table('admission_applications')
            ->where('tracking_token', $token)
            ->first();

        if ($legacy && (string) $legacy->date_of_birth === $dateOfBirth) {
            return $legacy;
        }

        return null;
    }

    public static function requestBasic(Request $request): array
    {
        return $request->validate([
            'token' => 'required|string|max:120',
            'date_of_birth' => 'required|date',
        ]);
    }
}
