<?php

namespace App\Services;

use App\Models\BackupJob;
use App\Models\RestoreJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class BackupRestoreService
{
    private array $backupTables = [
        'users',
        'roles',
        'permissions',
        'model_has_roles',
        'menu_items',
        'menu_permissions',
        'items',
        'subjects',
        'invoices',
        'payments',
        'tutoring_sessions',
        'disputes',
        'teacher_payouts',
        'web_settings',
        'system_settings',
    ];

    public function createBackup(string $type, string $mode, ?int $createdBy, ?string $note = null): BackupJob
    {
        $timestamp = now()->format('Ymd_His');
        $relativeDir = str_replace('\\', '/', env('BACKUP_PATH', 'storage/app/backups'));
        $absoluteDir = base_path($relativeDir);
        if (!File::isDirectory($absoluteDir)) {
            File::makeDirectory($absoluteDir, 0755, true);
        }

        $payload = [
            'meta' => [
                'created_at' => now()->toIso8601String(),
                'type' => $type,
                'mode' => $mode,
            ],
            'tables' => [],
        ];

        foreach ($this->backupTables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $payload['tables'][$table] = DB::table($table)->get()->map(fn ($row) => (array) $row)->all();
        }

        $filename = "backup_{$type}_{$timestamp}.json";
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;
        $json = json_encode($payload, JSON_PRETTY_PRINT);
        File::put($absolutePath, $json);

        return BackupJob::create([
            'type' => strtoupper($type),
            'mode' => strtoupper($mode),
            'file_path' => $absolutePath,
            'created_by' => $createdBy,
            'file_size' => filesize($absolutePath) ?: 0,
            'checksum_hash' => hash('sha256', $json),
            'note' => $note,
            'status' => 'CREATED',
        ]);
    }

    public function previewPartialRestore(BackupJob $backupJob): array
    {
        $payload = $this->readPayload($backupJob->file_path);
        $diff = [];

        foreach ($payload['tables'] ?? [] as $table => $rows) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            $diff[$table] = [
                'backup_rows' => count($rows),
                'current_rows' => DB::table($table)->count(),
                'mode' => 'merge_upsert',
            ];
        }

        return $diff;
    }

    public function applyPartialRestore(BackupJob $backupJob, ?int $requestedBy, ?string $reason = null): RestoreJob
    {
        $payload = $this->readPayload($backupJob->file_path);
        $diff = $this->previewPartialRestore($backupJob);

        DB::transaction(function () use ($payload) {
            foreach ($payload['tables'] ?? [] as $table => $rows) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                if (!in_array('id', $columns, true)) {
                    continue;
                }

                foreach ($rows as $row) {
                    $data = array_intersect_key((array) $row, array_flip($columns));
                    if (!array_key_exists('id', $data)) {
                        continue;
                    }
                    DB::table($table)->updateOrInsert(['id' => $data['id']], $data);
                }
            }
        });

        return RestoreJob::create([
            'backup_job_id' => $backupJob->id,
            'mode' => 'PARTIAL',
            'requested_by' => $requestedBy,
            'status' => 'APPLIED',
            'diff_preview' => $diff,
            'reason' => $reason,
            'executed_at' => now(),
        ]);
    }

    public function applyDisasterRestore(BackupJob $backupJob, ?int $requestedBy, ?string $reason = null): RestoreJob
    {
        $payload = $this->readPayload($backupJob->file_path);

        DB::transaction(function () use ($payload) {
            Schema::disableForeignKeyConstraints();
            foreach ($payload['tables'] ?? [] as $table => $rows) {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                DB::table($table)->delete();
                if (empty($rows)) {
                    continue;
                }

                $columns = Schema::getColumnListing($table);
                $insertRows = [];
                foreach ($rows as $row) {
                    $insertRows[] = array_intersect_key((array) $row, array_flip($columns));
                }

                if (!empty($insertRows)) {
                    DB::table($table)->insert($insertRows);
                }
            }
            Schema::enableForeignKeyConstraints();
        });

        return RestoreJob::create([
            'backup_job_id' => $backupJob->id,
            'mode' => 'DISASTER',
            'requested_by' => $requestedBy,
            'status' => 'APPLIED',
            'reason' => $reason,
            'executed_at' => now(),
        ]);
    }

    private function readPayload(string $path): array
    {
        if (!File::exists($path)) {
            throw new \RuntimeException('Backup file not found.');
        }

        return json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
