<?php

namespace App\Services;

use App\Models\ImportJob;
use App\Models\ImportJobDetail;
use App\Models\Item;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class ImportService
{
    public function importUsers(UploadedFile $file, ?int $requestedBy): ImportJob
    {
        $rows = $this->parseCsv($file);
        $job = ImportJob::create([
            'type' => 'users',
            'requested_by' => $requestedBy,
            'status' => 'RUNNING',
            'total_rows' => count($rows),
        ]);

        $success = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            try {
                if (empty($row['email']) || empty($row['name'])) {
                    throw new \RuntimeException('Missing name/email.');
                }

                $user = User::updateOrCreate(
                    ['email' => strtolower(trim($row['email']))],
                    [
                        'name' => trim($row['name']),
                        'password' => Hash::make($row['password'] ?? 'password123'),
                        'phone' => $row['phone'] ?? null,
                        'is_active' => true,
                        'email_verified_at' => now(),
                    ]
                );

                if (!empty($row['role'])) {
                    $user->syncRoles([$row['role']]);
                }

                ImportJobDetail::create([
                    'import_job_id' => $job->id,
                    'row_number' => $index + 1,
                    'status' => 'SUCCESS',
                    'message' => 'Imported',
                    'payload' => $row,
                ]);
                $success++;
            } catch (\Throwable $e) {
                ImportJobDetail::create([
                    'import_job_id' => $job->id,
                    'row_number' => $index + 1,
                    'status' => 'FAILED',
                    'message' => $e->getMessage(),
                    'payload' => $row,
                ]);
                $failed++;
            }
        }

        $job->update([
            'status' => 'DONE',
            'success_rows' => $success,
            'failed_rows' => $failed,
        ]);

        return $job;
    }

    public function importItems(UploadedFile $file, ?int $requestedBy): ImportJob
    {
        $rows = $this->parseCsv($file);
        $job = ImportJob::create([
            'type' => 'items',
            'requested_by' => $requestedBy,
            'status' => 'RUNNING',
            'total_rows' => count($rows),
        ]);

        $success = 0;
        $failed = 0;

        foreach ($rows as $index => $row) {
            try {
                if (empty($row['sku']) || empty($row['name'])) {
                    throw new \RuntimeException('Missing sku/name.');
                }

                Item::updateOrCreate(
                    ['sku' => trim($row['sku'])],
                    [
                        'name' => trim($row['name']),
                        'description' => $row['description'] ?? null,
                        'price' => (float) ($row['price'] ?? 0),
                        'stock' => (int) ($row['stock'] ?? 0),
                        'is_active' => true,
                    ]
                );

                ImportJobDetail::create([
                    'import_job_id' => $job->id,
                    'row_number' => $index + 1,
                    'status' => 'SUCCESS',
                    'message' => 'Imported',
                    'payload' => $row,
                ]);
                $success++;
            } catch (\Throwable $e) {
                ImportJobDetail::create([
                    'import_job_id' => $job->id,
                    'row_number' => $index + 1,
                    'status' => 'FAILED',
                    'message' => $e->getMessage(),
                    'payload' => $row,
                ]);
                $failed++;
            }
        }

        $job->update([
            'status' => 'DONE',
            'success_rows' => $success,
            'failed_rows' => $failed,
        ]);

        return $job;
    }

    private function parseCsv(UploadedFile $file): array
    {
        $content = file($file->getRealPath(), FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (empty($content)) {
            return [];
        }

        $headers = str_getcsv(array_shift($content));
        $rows = [];

        foreach ($content as $line) {
            $values = str_getcsv($line);
            $rows[] = array_combine($headers, array_pad($values, count($headers), null));
        }

        return $rows;
    }
}
