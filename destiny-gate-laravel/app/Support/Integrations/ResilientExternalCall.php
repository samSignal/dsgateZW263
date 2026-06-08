<?php

namespace App\Support\Integrations;

use Illuminate\Http\Request;

class ResilientExternalCall
{
    public static function call(
        string $systemCode,
        string $operation,
        array $ctx,
        callable $fn,
        ?int $applicationId,
        ?int $actorUserId,
        ?Request $request,
        array $policy = []
    ): array {
        $systemCode = trim($systemCode);
        $operation = trim($operation);
        $start = hrtime(true);

        $cbPolicy = (array) ($policy['circuit_breaker'] ?? (array) config('admissions.operational.reliability.circuit_breaker', []));
        $retryPolicy = (array) ($policy['retry'] ?? (array) config('admissions.operational.reliability.retry', []));

        $gate = ExternalCircuitBreakerService::allow($systemCode, $cbPolicy);
        if (empty($gate['allowed'])) {
            return [
                'ok' => false,
                'code' => 'CIRCUIT_OPEN',
                'message' => 'Downstream system is temporarily unavailable.',
                'retry_after_seconds' => (int) ($gate['retry_after_seconds'] ?? 0),
            ];
        }

        $result = null;
        try {
            $result = $fn();
        } catch (\Throwable $e) {
            $result = [
                'ok' => false,
                'code' => 'EXCEPTION',
                'message' => $e->getMessage(),
            ];
        }

        $ok = !empty($result['ok']);
        $code = is_string($result['code'] ?? null) ? (string) $result['code'] : ($ok ? 'OK' : 'FAILED');

        if ($ok) {
            ExternalCircuitBreakerService::recordSuccess($systemCode, $cbPolicy);
        } else {
            ExternalCircuitBreakerService::recordFailure($systemCode, [
                'operation' => $operation,
                'code' => $code,
                'message' => $result['message'] ?? null,
                'ctx' => [
                    'correlation_id' => $ctx['correlation_id'] ?? null,
                    'request_id' => $ctx['request_id'] ?? null,
                ],
            ], $cbPolicy);
        }

        if (!$ok && self::isTransientFailure($code)) {
            if (!empty($retryPolicy['enabled'])) {
                ExternalRetryQueueService::enqueue(
                    $systemCode,
                    $operation,
                    [
                        'ctx' => $ctx,
                        'result_code' => $code,
                        'result_message' => $result['message'] ?? null,
                    ],
                    isset($ctx['step_idempotency_key']) ? (string) $ctx['step_idempotency_key'] : null,
                    isset($ctx['correlation_id']) ? (string) $ctx['correlation_id'] : null,
                    $applicationId,
                    $retryPolicy
                );
            }
        }

        return $result;
    }

    private static function isTransientFailure(string $code): bool
    {
        return in_array($code, ['TIMEOUT', 'NETWORK_ERROR', 'EXTERNAL_5XX', 'CIRCUIT_OPEN', 'EXCEPTION'], true);
    }
}
