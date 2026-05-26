<?php

namespace App\Support\Admissions;

use Illuminate\Http\JsonResponse;

class AdmissionResponder
{
    public static function ok(array $data = [], array $meta = []): JsonResponse
    {
        return response()->json(['ok' => true, 'data' => $data, 'meta' => $meta]);
    }

    public static function fail(string $code, string $message, int $status, array $errors = [], array $meta = []): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'code' => $code,
            'message' => $message,
            'errors' => empty($errors) ? (object) [] : $errors,
            'meta' => $meta,
        ], $status);
    }
}

