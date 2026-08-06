<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RenumberStudents extends Command
{
    protected $signature = 'dgi:renumber-students {--dry-run : Report the old/new mapping without updating anything}';
    protected $description = 'Renumber every student to the W0{YY}{NNNN}{L} format, assigning sequence per admission year in admission_date/id order. Also updates the linked login username where one exists.';

    public function handle(): int
    {
        $students = DB::table('students')->orderBy('admission_date')->orderBy('id')->get();

        if ($students->isEmpty()) {
            $this->info('No students found.');
            return self::SUCCESS;
        }

        $seqPerYear = [];
        $rows = [];
        $updates = [];

        foreach ($students as $s) {
            $year = date('Y', strtotime($s->admission_date));
            $yy = substr($year, -2);
            $seqPerYear[$year] = ($seqPerYear[$year] ?? 0) + 1;
            $seq = str_pad($seqPerYear[$year], 4, '0', STR_PAD_LEFT);
            $letter = chr(random_int(65, 90));
            $newNumber = "W0{$yy}{$seq}{$letter}";

            $rows[] = [
                $s->id,
                "{$s->first_name} {$s->last_name}",
                $s->student_number ?: '(none)',
                $newNumber,
                $s->user_id ? 'yes' : 'no',
            ];

            $updates[] = ['id' => $s->id, 'user_id' => $s->user_id, 'new_number' => $newNumber];
        }

        $this->table(['ID', 'Name', 'Old Number', 'New Number', 'Has Login'], $rows);

        if ($this->option('dry-run')) {
            $this->info('Dry run — no changes made.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($updates) {
            foreach ($updates as $u) {
                DB::table('students')->where('id', $u['id'])->update([
                    'student_number'   => $u['new_number'],
                    'admission_number' => $u['new_number'],
                    'updated_at'       => now(),
                ]);

                if ($u['user_id']) {
                    DB::table('users')->where('id', $u['user_id'])->update([
                        'username'   => $u['new_number'],
                        'updated_at' => now(),
                    ]);
                }
            }
        });

        $this->info('Renumbered ' . count($updates) . ' student(s). Students with a login account also had their username updated to match.');

        return self::SUCCESS;
    }
}
