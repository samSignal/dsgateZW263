<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class BackfillStudentLogins extends Command
{
    protected $signature = 'dgi:backfill-student-logins {--dry-run : Report which students would get an account without creating one}';
    protected $description = 'Create a login account (username = student_number, password = national_id or the standard default) for any student that has none, same convention as StudentApiController::store.';

    public function handle(): int
    {
        $students = DB::table('students')->whereNull('user_id')->orderBy('id')->get();

        if ($students->isEmpty()) {
            $this->info('No students are missing a login account.');
            return self::SUCCESS;
        }

        $rows = $students->map(fn ($s) => [
            $s->id, "{$s->first_name} {$s->last_name}", $s->student_number,
            $s->national_id ? 'national ID' : 'default (DGI@' . date('Y') . '!)',
        ])->all();
        $this->table(['ID', 'Name', 'Username', 'Password source'], $rows);

        if ($this->option('dry-run')) {
            $this->info('Dry run — no accounts created.');
            return self::SUCCESS;
        }

        $created = 0;
        DB::transaction(function () use ($students, &$created) {
            foreach ($students as $s) {
                if (!$s->student_number) {
                    $this->warn("Skipping student #{$s->id} — no student_number to use as username.");
                    continue;
                }

                $email = $s->email;
                if ($email && DB::table('users')->where('email', $email)->exists()) {
                    $email = null; // users.email is globally unique — same guard as elsewhere
                }

                $password = $s->national_id ?: ('DGI@' . date('Y') . '!');

                $userId = DB::table('users')->insertGetId([
                    'name'                 => "{$s->first_name} {$s->last_name}",
                    'email'                => $email,
                    'username'             => $s->student_number,
                    'password'             => Hash::make($password),
                    'role'                 => 'student',
                    'is_active'            => true,
                    'must_change_password' => true,
                    'created_at'           => now(),
                    'updated_at'           => now(),
                    'last_signed_in'       => now(),
                ]);

                DB::table('students')->where('id', $s->id)->update([
                    'user_id'    => $userId,
                    'updated_at' => now(),
                ]);

                $created++;
            }
        });

        $this->info("Created {$created} student login account(s).");

        return self::SUCCESS;
    }
}
