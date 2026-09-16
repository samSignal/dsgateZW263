<?php

namespace App\Console\Commands;

use App\Mail\DatabaseBackupMail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'dgi:backup-database
        {--keep-days=14 : Delete local backup copies older than this many days}
        {--force : Email the backup even if the data has not changed since the last run}';

    protected $description = 'Dump the database and email the compressed dump to the configured backup address, skipping the send when nothing has changed since the last run.';

    public function handle(): int
    {
        $recipient = config('backup.email');
        if (!$recipient) {
            $this->error('DB_BACKUP_EMAIL is not set — nowhere to send the backup.');
            return self::FAILURE;
        }

        $connection = config('database.default');
        $db         = config("database.connections.{$connection}");
        $database   = $db['database'];

        $this->info("Dumping database \"{$database}\"…");

        $process = new Process([
            'mysqldump', '--single-transaction', '--quick', '--lock-tables=false',
            '-h', $db['host'], '-P', (string) $db['port'], '-u', $db['username'], $database,
        ]);
        $process->setTimeout(600);
        $process->setEnv(['MYSQL_PWD' => $db['password']]);
        $process->run();

        if (!$process->isSuccessful()) {
            $this->error('mysqldump failed: ' . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        $sql = $process->getOutput();

        // mysqldump stamps a "-- Dump completed on <timestamp>" trailer on every run, which
        // would make an otherwise byte-identical dump hash differently every single time.
        // Strip it before hashing so the hash only reflects the actual data.
        $normalized = preg_replace('/^-- Dump completed on .*$/m', '', $sql);
        $hash       = hash('sha256', $normalized);

        $hashFile     = storage_path('app/.db_backup_hash');
        $previousHash = File::exists($hashFile) ? trim(File::get($hashFile)) : null;

        if ($hash === $previousHash && !$this->option('force')) {
            $this->info('No data changes since the last backup — skipping email.');
            return self::SUCCESS;
        }

        $directory = storage_path('app/backups');
        File::ensureDirectoryExists($directory);

        $filename = sprintf('%s-%s.sql.gz', $database, now()->format('Y-m-d_His'));
        $path     = "{$directory}/{$filename}";
        File::put($path, gzencode($sql, 9));

        $sizeMb = round(File::size($path) / 1024 / 1024, 2);
        $this->info("Data changed — dump complete ({$sizeMb} MB). Emailing to {$recipient}…");

        Mail::to($recipient)->send(new DatabaseBackupMail($path, $database, $sizeMb));
        File::put($hashFile, $hash);

        $this->pruneOldBackups($directory, (int) $this->option('keep-days'));

        $this->info('Backup emailed successfully.');
        return self::SUCCESS;
    }

    private function pruneOldBackups(string $directory, int $keepDays): void
    {
        $cutoff = now()->subDays($keepDays)->getTimestamp();

        foreach (File::files($directory) as $file) {
            if ($file->getMTime() < $cutoff) {
                File::delete($file->getPathname());
            }
        }
    }
}
