<?php

namespace App\Console\Commands;

use App\Support\StudentStreamResolver;
use Illuminate\Console\Command;

class BackfillStudentStreams extends Command
{
    protected $signature = 'dgi:backfill-student-streams {--dry-run : Report mappings without updating students}';
    protected $description = 'Backfill students.stream_id, form_id, category_id, and academic_year_id from legacy classes.';

    public function handle(): int
    {
        $report = StudentStreamResolver::backfillStudents((bool) $this->option('dry-run'));

        $this->info(($this->option('dry-run') ? 'Dry run complete.' : 'Backfill complete.'));
        $this->line('Updated: ' . $report['updated']);
        $this->line('Skipped existing stream_id: ' . $report['skipped_existing_stream']);
        $this->line('Unmapped: ' . count($report['unmapped']));
        $this->line('Duplicate mappings: ' . count($report['duplicates']));
        $this->line('Failed: ' . count($report['failed']));

        foreach (['unmapped', 'duplicates', 'failed'] as $key) {
            if (!empty($report[$key])) {
                $this->warn(strtoupper($key));
                $this->table(array_keys($report[$key][0]), $report[$key]);
            }
        }

        return empty($report['failed']) ? self::SUCCESS : self::FAILURE;
    }
}
