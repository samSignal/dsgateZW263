<?php

namespace App\Console\Commands;

use App\Support\Admissions\AdmissionAudit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdmissionsArchiveIntakeClosedDrafts extends Command
{
    protected $signature = 'admissions:archive-intake-closed-drafts {--dry-run : Report without updating}';
    protected $description = 'Archive draft applications when their intake has closed.';

    public function handle(): int
    {
        if (!config('admissions.intake.archive_drafts_on_close', true)) {
            $this->line('Intake close archival is disabled.');
            return self::SUCCESS;
        }

        $closedYears = DB::table('admission_intakes')
            ->where('is_active', true)
            ->whereNotNull('closes_at')
            ->where('closes_at', '<=', now())
            ->pluck('academic_year_id')
            ->all();

        if (empty($closedYears)) {
            $this->line('No closed intakes found.');
            return self::SUCCESS;
        }

        $q = DB::table('admission_applications')
            ->whereNull('archived_at')
            ->whereNull('locked_at')
            ->where('status', 'draft')
            ->whereIn('academic_year_id', $closedYears);

        $count = (int) $q->count();
        $this->line('Drafts to archive due to intake close: ' . $count);
        if ($count === 0) return self::SUCCESS;

        if ($this->option('dry-run')) {
            $this->table(['id', 'application_number', 'academic_year_id', 'updated_at'], $q->limit(25)->get()->map(fn ($r) => (array) $r)->all());
            return self::SUCCESS;
        }

        $apps = $q->select('id')->get();
        foreach ($apps as $row) {
            DB::table('admission_applications')->where('id', $row->id)->update([
                'archived_at' => now(),
                'archived_reason' => 'archived_intake_closed',
                'lifecycle_state' => 'DRAFT_EXPIRED_ARCHIVED',
                'updated_at' => now(),
            ]);
            AdmissionAudit::log((int) $row->id, 'system', null, 'admissions.lifecycle.archived_intake_closed', [], 'info');
        }

        $this->info('Archived: ' . count($apps));
        return self::SUCCESS;
    }
}

