<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--path= : Custom backup directory}
        {--keep-days=7 : Number of days to retain backups (0 to disable pruning)}
        {--no-compression : Do not compress backup file with gzip}';

    protected $description = 'Backup database securely using mysqldump and compress with gzip';

    public function handle(): int
    {
        $startTime = microtime(true);
        $connection = config('database.default');
        $dbConfig = config("database.connections.{$connection}");

        if (!$dbConfig || ($dbConfig['driver'] ?? '') !== 'mysql') {
            $this->error("Backup only supports MySQL driver. Current default: {$connection}");
            return Command::FAILURE;
        }

        $host = (string) ($dbConfig['host'] ?? '127.0.0.1');
        $port = (string) ($dbConfig['port'] ?? '3306');
        $database = (string) ($dbConfig['database'] ?? '');
        $username = (string) ($dbConfig['username'] ?? '');
        $password = (string) ($dbConfig['password'] ?? '');

        if (empty($database)) {
            $this->error('Database name is not configured.');
            return Command::FAILURE;
        }

        $backupDir = $this->option('path') ?: storage_path('app/backups');
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d_H-i-s');
        $isGzip = !$this->option('no-compression');
        $filename = "backup_{$database}_{$timestamp}." . ($isGzip ? 'sql.gz' : 'sql');
        $targetFile = rtrim($backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

        $this->info("Starting database backup for '{$database}'...");

        // Build command using environment variables for credentials to avoid exposure in ps
        if ($isGzip) {
            $command = 'mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --single-transaction --quick --routines --triggers "$DB_DATABASE" | gzip > "$BACKUP_PATH"';
        } else {
            $command = 'mysqldump --host="$DB_HOST" --port="$DB_PORT" --user="$DB_USERNAME" --single-transaction --quick --routines --triggers "$DB_DATABASE" > "$BACKUP_PATH"';
        }

        $env = [
            'MYSQL_PWD' => $password,
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_USERNAME' => $username,
            'DB_DATABASE' => $database,
            'BACKUP_PATH' => $targetFile,
        ];

        $process = Process::fromShellCommandLine($command, null, $env, null, 600);
        $process->run();

        if (!$process->isSuccessful() || !File::exists($targetFile) || File::size($targetFile) === 0) {
            $errorOutput = $process->getErrorOutput() ?: 'Unknown backup failure (empty file produced)';
            $this->error("Database backup failed: {$errorOutput}");
            Log::error('Database backup failed', [
                'database' => $database,
                'error' => $errorOutput,
                'exit_code' => $process->getExitCode(),
            ]);

            if (File::exists($targetFile) && File::size($targetFile) === 0) {
                File::delete($targetFile);
            }

            return Command::FAILURE;
        }

        $duration = round(microtime(true) - $startTime, 2);
        $sizeBytes = File::size($targetFile);
        $sizeFormatted = $this->formatBytes($sizeBytes);

        $this->info("Backup completed successfully in {$duration}s!");
        $this->line("File: <comment>{$targetFile}</comment> ({$sizeFormatted})");

        Log::info('Database backup succeeded', [
            'database' => $database,
            'file' => $targetFile,
            'size' => $sizeFormatted,
            'duration_seconds' => $duration,
        ]);

        $this->pruneOldBackups($backupDir, (int) $this->option('keep-days'));

        return Command::SUCCESS;
    }

    private function pruneOldBackups(string $backupDir, int $keepDays): void
    {
        if ($keepDays <= 0) {
            return;
        }

        $cutoff = Carbon::now()->subDays($keepDays);
        $files = File::files($backupDir);
        $deletedCount = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if ($ext !== 'gz' && $ext !== 'sql') {
                continue;
            }

            if (Carbon::createFromTimestamp($file->getMTime())->lt($cutoff)) {
                File::delete($file->getPathname());
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup file(s) older than {$keepDays} days.");
        }
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
