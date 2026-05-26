<?php

namespace App\Support\Admissions;

use Illuminate\Http\Request;

class AdmissionSecurityHasher
{
    public static function keyHash(?string $value): ?string
    {
        if ($value === null) return null;
        $v = trim($value);
        if ($v === '') return null;
        return hash_hmac('sha256', $v, (string) config('app.key'));
    }

    public static function ipHash(?string $ip): ?string
    {
        if (!$ip) return null;
        return hash_hmac('sha256', $ip, (string) config('app.key'));
    }

    public static function deviceHash(Request $request): string
    {
        $ua = (string) $request->userAgent();
        $lang = (string) $request->header('accept-language', '');
        $enc = (string) $request->header('accept-encoding', '');
        $secUa = (string) $request->header('sec-ch-ua', '');
        $secPlat = (string) $request->header('sec-ch-ua-platform', '');
        $raw = $ua . '|' . $lang . '|' . $enc . '|' . $secUa . '|' . $secPlat;
        return hash_hmac('sha256', $raw, (string) config('app.key'));
    }

    public static function correlationId(Request $request): string
    {
        $incoming = (string) $request->header('x-correlation-id', '');
        if ($incoming !== '' && strlen($incoming) <= 80) return $incoming;
        return (string) \Illuminate\Support\Str::uuid();
    }
}
