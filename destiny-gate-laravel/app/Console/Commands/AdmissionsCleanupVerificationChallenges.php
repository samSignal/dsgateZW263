<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdmissionsCleanupVerificationChallenges extends Command
{
    protected $signature = 'admissions:cleanup-verification-challenges';
    protected $description = 'Revoke expired admissions verification challenges.';

    public function handle(): int
    {
        $now = now();
        $count = DB::table('admission_verification_challenges')
            ->whereNull('used_at')
            ->whereNull('revoked_at')
            ->where('expires_at', '<', $now)
            ->update(['revoked_at' => $now, 'updated_at' => $now]);

        $this->line('Revoked expired challenges: ' . $count);
        return self::SUCCESS;
    }
}

