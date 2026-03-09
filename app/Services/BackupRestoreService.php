<?php

namespace App\Services;

use App\Models\BackupJob;
use App\Models\RestoreJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackupRestoreService
{
    private array $preservedTablesOnWipe = [
        'migrations',
        'jobs',
        'job_batches',
        'failed_jobs',
        'cache',
        'cache_locks',
        'sessions',
        'password_reset_tokens',
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'model_has_permissions',
        'role_has_permissions',
        'menu_items',
        'menu_permissions',
        'system_settings',
        'web_settings',
        'backup_jobs',
        'restore_jobs',
    ];

    public function createBackup(string $type, string $mode, ?int $createdBy, ?string $note = null): BackupJob
    {
        $timestamp = now()->format('Ymd_His');
        $absoluteDir = $this->backupDirectory();
        $filename = "backup_{$type}_{$timestamp}.sql";
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
        $sql = $this->generateSqlDump();

        File::put($absolutePath, $sql);

        return BackupJob::create([
            'type' => strtoupper($type),
            'mode' => strtoupper($mode),
            'file_path' => $absolutePath,
            'created_by' => $createdBy,
            'file_size' => filesize($absolutePath) ?: 0,
            'checksum_hash' => hash('sha256', $sql),
            'note' => $note,
            'status' => 'CREATED',
        ]);
    }

    public function restoreFromBackupJob(BackupJob $backupJob, ?int $requestedBy, ?string $reason = null, bool $wipeFirst = true): RestoreJob
    {
        $this->applySqlFile($backupJob->file_path, $wipeFirst);

        return RestoreJob::create([
            'backup_job_id' => $backupJob->id,
            'mode' => $wipeFirst ? 'DISASTER' : 'PARTIAL',
            'requested_by' => $requestedBy,
            'status' => 'APPLIED',
            'reason' => $reason,
            'executed_at' => now(),
        ]);
    }

    public function restoreFromUpload(UploadedFile $file, ?int $requestedBy, ?string $reason = null, bool $wipeFirst = true): RestoreJob
    {
        $timestamp = now()->format('Ymd_His');
        $absoluteDir = $this->backupDirectory();
        $filename = "uploaded_restore_{$timestamp}.sql";
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
        $sql = (string) File::get($file->getRealPath());
        File::put($absolutePath, $sql);

        $backupJob = BackupJob::create([
            'type' => 'DB',
            'mode' => 'UPLOAD',
            'file_path' => $absolutePath,
            'created_by' => $requestedBy,
            'file_size' => strlen($sql),
            'checksum_hash' => hash('sha256', $sql),
            'note' => 'Uploaded SQL restore file',
            'status' => 'UPLOADED',
        ]);

        $this->applySqlStatements($sql, $wipeFirst);

        return RestoreJob::create([
            'backup_job_id' => $backupJob->id,
            'mode' => $wipeFirst ? 'UPLOAD_RESTORE' : 'UPLOAD_MERGE',
            'requested_by' => $requestedBy,
            'status' => 'APPLIED',
            'reason' => $reason,
            'executed_at' => now(),
        ]);
    }

    public function wipeDatabaseData(?int $preserveUserId = null): void
    {
        $tables = $this->databaseTables();

        DB::beginTransaction();
        try {
            Schema::disableForeignKeyConstraints();
            foreach (array_reverse($tables) as $table) {
                if (in_array($table, $this->preservedTablesOnWipe, true)) {
                    if ($table === 'users' && $preserveUserId) {
                        DB::table('users')->where('id', '!=', $preserveUserId)->delete();
                    }
                    continue;
                }

                DB::table($table)->delete();
            }
            Schema::enableForeignKeyConstraints();
            DB::commit();
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            DB::rollBack();
            throw $e;
        }
    }

    public function downloadResponse(BackupJob $backupJob)
    {
        if (!File::exists($backupJob->file_path)) {
            throw new \RuntimeException('Backup file not found.');
        }

        return response()->download($backupJob->file_path, basename($backupJob->file_path), [
            'Content-Type' => 'application/sql',
        ]);
    }

    private function applySqlFile(string $path, bool $wipeFirst): void
    {
        if (!File::exists($path)) {
            throw new \RuntimeException('Backup file not found.');
        }

        $this->applySqlStatements((string) File::get($path), $wipeFirst);
    }

    private function applySqlStatements(string $sql, bool $wipeFirst): void
    {
        DB::beginTransaction();
        try {
            if ($wipeFirst) {
                $this->wipeDatabaseData((int) auth()->id());
            }

            Schema::disableForeignKeyConstraints();
            foreach ($this->splitSqlStatements($sql) as $statement) {
                $trimmed = trim($statement);
                if ($trimmed === '') {
                    continue;
                }
                DB::unprepared($trimmed);
            }
            Schema::enableForeignKeyConstraints();
            DB::commit();
        } catch (\Throwable $e) {
            Schema::enableForeignKeyConstraints();
            DB::rollBack();
            throw $e;
        }
    }

    private function generateSqlDump(): string
    {
        $pdo = DB::connection()->getPdo();
        $sql = [];
        $sql[] = '-- Priviate SQL Backup';
        $sql[] = '-- Generated at: ' . now()->toDateTimeString();
        $sql[] = 'SET FOREIGN_KEY_CHECKS=0;';

        foreach ($this->databaseTables() as $table) {
            $createRow = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createArray = (array) $createRow;
            $createSql = (string) array_values($createArray)[1];

            $sql[] = '';
            $sql[] = "-- Table: {$table}";
            $sql[] = "DROP TABLE IF EXISTS `{$table}`;";
            $sql[] = $createSql . ';';

            $rows = DB::table($table)->get();
            if ($rows->isEmpty()) {
                continue;
            }

            $columns = Schema::getColumnListing($table);
            $columnList = implode(', ', array_map(fn ($col) => "`{$col}`", $columns));

            foreach ($rows as $row) {
                $rowArray = (array) $row;
                $values = [];
                foreach ($columns as $column) {
                    $values[] = $this->sqlLiteral($rowArray[$column] ?? null, $pdo);
                }
                $sql[] = "INSERT INTO `{$table}` ({$columnList}) VALUES (" . implode(', ', $values) . ');';
            }
        }

        $sql[] = 'SET FOREIGN_KEY_CHECKS=1;';

        return implode(PHP_EOL, $sql) . PHP_EOL;
    }

    private function sqlLiteral(mixed $value, \PDO $pdo): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value) ?: "''";
    }

    private function splitSqlStatements(string $sql): array
    {
        $statements = [];
        $buffer = '';
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $length = strlen($sql);

        for ($i = 0; $i < $length; $i++) {
            $char = $sql[$i];
            $prev = $i > 0 ? $sql[$i - 1] : '';

            if ($char === "'" && !$inDoubleQuote && $prev !== '\\') {
                $inSingleQuote = !$inSingleQuote;
            } elseif ($char === '"' && !$inSingleQuote && $prev !== '\\') {
                $inDoubleQuote = !$inDoubleQuote;
            }

            if ($char === ';' && !$inSingleQuote && !$inDoubleQuote) {
                $statements[] = $buffer;
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        if (trim($buffer) !== '') {
            $statements[] = $buffer;
        }

        return $statements;
    }

    private function databaseTables(): array
    {
        $tables = [];
        $rows = DB::select('SHOW TABLES');
        foreach ($rows as $row) {
            $tables[] = (string) array_values((array) $row)[0];
        }

        return $tables;
    }

    private function backupDirectory(): string
    {
        $relativeDir = str_replace('\\', '/', env('BACKUP_PATH', 'storage/app/backups'));
        $absoluteDir = base_path($relativeDir);
        if (!File::isDirectory($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        return $absoluteDir;
    }
}
