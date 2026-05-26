<?php

namespace App\Support\Admissions;

class AdmissionNormalizer
{
    public static function normalizeBirthCertificate(?string $value): ?string
    {
        if ($value === null) return null;
        $v = strtoupper(trim($value));
        $v = preg_replace('/\s+/', '', $v);
        $v = preg_replace('/[^A-Z0-9\-]/', '', $v);
        $v = str_replace('--', '-', $v);
        return $v === '' ? null : $v;
    }

    public static function normalizeName(?string $value): ?string
    {
        if ($value === null) return null;
        $v = trim(preg_replace('/\s+/', ' ', $value));
        return $v === '' ? null : $v;
    }

    public static function normalizeEmail(?string $value): ?string
    {
        if ($value === null) return null;
        $v = strtolower(trim($value));
        return $v === '' ? null : $v;
    }
}

