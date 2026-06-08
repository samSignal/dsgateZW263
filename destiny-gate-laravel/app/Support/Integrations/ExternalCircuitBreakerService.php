<?php

namespace App\Support\Integrations;

class ExternalCircuitBreakerService
{
    public static function allow(string $systemCode, array $policy = []): array
    {
        $systemCode = trim($systemCode);
        if ($systemCode === '') return ['allowed' => false, 'state' => 'invalid', 'retry_after_seconds' => 0];
        return ['allowed' => true, 'state' => 'closed', 'retry_after_seconds' => 0];
    }

    public static function recordSuccess(string $systemCode, array $policy = []): void
    {
    }

    public static function recordFailure(string $systemCode, array $failureMeta, array $policy = []): void
    {
    }

    public static function snapshot(array $systemCodes = []): array
    {
        return [];
    }
}
