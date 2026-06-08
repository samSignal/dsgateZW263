<?php

namespace App\Support\Integrations;

class ExternalRetryQueueService
{
    public static function enqueue(string $systemCode, string $operation, array $payload, ?string $idempotencyKey, ?string $correlationId, ?int $applicationId, array $policy = []): array
    {
        $systemCode = trim($systemCode);
        $operation = trim($operation);
        if ($systemCode === '' || $operation === '') return ['ok' => false, 'code' => 'INVALID'];
        return ['ok' => true, 'enqueued' => false, 'idempotent' => false, 'code' => 'DISABLED'];
    }

    public static function claimBatch(int $limit = 50): array
    {
        return [];
    }

    public static function markSucceeded(int $id, array $result = []): void
    {
    }

    public static function markFailed(int $id, array $error, array $policy = []): array
    {
        return ['status' => 'disabled'];
    }

    public static function stats(): array
    {
        return [
            'by_status' => [],
            'pending_due' => 0,
        ];
    }
}
