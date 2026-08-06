<?php

namespace App\Console\Commands;

use App\Models\Application;
use Illuminate\Console\Command;

class ExpireStaleOffers extends Command
{
    protected $signature = 'dgi:expire-stale-offers {--dry-run : Report which offers would expire without updating them}';
    protected $description = 'Expire offered applications past their offer_letter_expires_at deadline with no acceptance, freeing the seat for the waiting list.';

    public function handle(): int
    {
        $stale = Application::where('status', 'offered')
            ->whereNotNull('offer_letter_expires_at')
            ->where('offer_letter_expires_at', '<', now())
            ->whereNull('offer_accepted_at')
            ->get(['id', 'application_number', 'first_name', 'last_name', 'offer_letter_expires_at']);

        if ($stale->isEmpty()) {
            $this->info('No stale offers found.');
            return self::SUCCESS;
        }

        $rows = $stale->map(fn ($a) => [
            $a->id, $a->application_number, "{$a->first_name} {$a->last_name}", $a->offer_letter_expires_at,
        ])->all();

        $this->table(['ID', 'Application #', 'Name', 'Expired At'], $rows);

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes made.');
            return self::SUCCESS;
        }

        Application::whereIn('id', $stale->pluck('id'))->update(['status' => 'expired']);
        $this->info("Expired {$stale->count()} stale offer(s).");

        return self::SUCCESS;
    }
}
