<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdmissionsCleanupSecurityCounters extends Command
{
    protected $signature = 'admissions:cleanup-security-counters';
    protected $description = 'Clear expired lockouts and prune stale security counters.';

    public function handle(): int
    {
        $now = now();
        $cleared = DB::table('admission_security_counters')
            ->whereNotNull('locked_until')
            ->where('locked_until', '<', $now)
            ->update(['locked_until' => null, 'updated_at' => $now]);

        $pruned = DB::table('admission_security_counters')
            ->whereNull('locked_until')
            ->where('count', 0)
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '<', $now->copy()->subDays(30))
            ->delete();

        $this->line('Cleared lockouts: ' . $cleared);
        $this->line('Pruned stale counters: ' . $pruned);
        return self::SUCCESS;
    }
}

