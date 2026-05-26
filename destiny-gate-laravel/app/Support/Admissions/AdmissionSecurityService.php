<?php

namespace App\Support\Admissions;

use Illuminate\Support\Facades\DB;

class AdmissionSecurityService
{
    public static function isLocked(string $counterType, string $keyHash): bool
    {
        $lockedUntil = DB::table('admission_security_counters')
            ->where('counter_type', $counterType)
            ->where('key_hash', $keyHash)
            ->value('locked_until');

        return $lockedUntil ? now()->lt($lockedUntil) : false;
    }

    public static function recordFailure(string $counterType, string $keyHash, int $threshold, int $lockoutMinutes): array
    {
        $now = now();
        $row = DB::table('admission_security_counters')
            ->where('counter_type', $counterType)
            ->where('key_hash', $keyHash)
            ->lockForUpdate()
            ->first();

        $count = 1;
        $lockedUntil = null;

        if ($row) {
            $count = (int) $row->count + 1;
            if ($count >= $threshold) {
                $lockedUntil = $now->copy()->addMinutes($lockoutMinutes);
            }
            DB::table('admission_security_counters')->where('id', $row->id)->update([
                'count' => $count,
                'last_seen_at' => $now,
                'locked_until' => $lockedUntil ?? $row->locked_until,
                'updated_at' => $now,
            ]);
        } else {
            if ($count >= $threshold) {
                $lockedUntil = $now->copy()->addMinutes($lockoutMinutes);
            }
            DB::table('admission_security_counters')->insert([
                'counter_type' => $counterType,
                'key_hash' => $keyHash,
                'count' => $count,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'locked_until' => $lockedUntil,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        return ['count' => $count, 'locked_until' => $lockedUntil];
    }

    public static function reset(string $counterType, string $keyHash): void
    {
        DB::table('admission_security_counters')
            ->where('counter_type', $counterType)
            ->where('key_hash', $keyHash)
            ->update(['count' => 0, 'locked_until' => null, 'updated_at' => now()]);
    }
}

