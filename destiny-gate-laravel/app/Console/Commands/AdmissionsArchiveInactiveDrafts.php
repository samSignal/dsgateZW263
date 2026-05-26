<?php

namespace App\Console\Commands;

use App\Support\Admissions\AdmissionAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdmissionsArchiveInactiveDrafts extends Command
{
    protected $signature = 'admissions:archive-inactive-drafts {--dry-run : Report without updating}';
    protected $description = 'Archive inactive admissions drafts based on configured inactivity window.';

    public function handle(): int
    {
        $days = (int) config('admissions.inactivity_expiry_days', 90);
        $cutoff = now()->subDays($days);

        $q = DB::table('admission_applications')
            ->whereNull('archived_at')
            ->whereNull('locked_at')
            ->where('status', 'draft')
            ->where(function ($w) {
                $w->whereNull('lifecycle_state')->orWhereIn('lifecycle_state', ['DRAFT_ACTIVE', 'DRAFT_REACTIVATED']);
            })
            ->where(function ($w) use ($cutoff) {
                $w->whereNotNull('last_activity_at')->where('last_activity_at', '<', $cutoff)
                    ->orWhere(function ($w2) use ($cutoff) {
                        $w2->whereNull('last_activity_at')->where('updated_at', '<', $cutoff);
                    });
            });

        $count = (int) $q->count();
        $this->line('Inactive drafts to archive: ' . $count);
        if ($count === 0) return self::SUCCESS;

        if ($this->option('dry-run')) {
            $this->table(['id', 'application_number', 'last_activity_at', 'updated_at'], $q->limit(25)->get()->map(fn ($r) => (array) $r)->all());
            return self::SUCCESS;
        }

        $apps = $q->select('id')->get();
        foreach ($apps as $row) {
            DB::table('admission_applications')->where('id', $row->id)->update([
                'archived_at' => now(),
                'archived_reason' => 'archived_inactive',
                'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                'updated_at' => now(),
            ]);
            AdmissionAudit::log((int) $row->id, 'system', null, 'admissions.lifecycle.archived_inactive', [], 'info');
        }

        $this->info('Archived: ' . count($apps));
        return self::SUCCESS;
    }
}

