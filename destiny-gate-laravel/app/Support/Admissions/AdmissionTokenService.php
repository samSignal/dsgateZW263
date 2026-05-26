<?php

namespace App\Support\Admissions;

use Illuminate\Support\Str;

class AdmissionTokenService
{
    public static function generatePlainToken(): string
    {
        return 'DGI-' . Str::upper(Str::random(8));
    }

    public static function tokenLast4(string $token): string
    {
        $t = strtoupper($token);
        return substr($t, max(0, strlen($t) - 4));
    }

    public static function hashToken(string $token): string
    {
        $key = (string) config('app.key');
        return hash_hmac('sha256', strtoupper($token), $key);
    }
}

