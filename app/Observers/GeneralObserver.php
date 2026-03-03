<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class GeneralObserver
{
    public function __construct(private readonly AuditService $auditService)
    {
    }

    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->auditService->log('CREATE', $model, [], $model->getAttributes(), $this->requestContext());
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $changes = $model->getChanges();
        $original = $model->getOriginal();

        if (Schema::hasTable('history_edits')) {
            foreach ($changes as $field => $newValue) {
                if (in_array($field, ['updated_at'], true)) {
                    continue;
                }

                DB::table('history_edits')->insert([
                    'user_id' => Auth::id(),
                    'model_type' => get_class($model),
                    'model_id' => $model->getKey(),
                    'field' => $field,
                    'old_value' => isset($original[$field]) ? (string) $original[$field] : null,
                    'new_value' => (string) $newValue,
                    'reason' => request('edit_reason', 'system_update'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->auditService->log('UPDATE', $model, $original, $model->getAttributes(), $this->requestContext());
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->auditService->log('SOFT_DELETE', $model, $model->getOriginal(), [], $this->requestContext());
    }

    public function deleting(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        if (Schema::hasColumn($model->getTable(), 'is_deleted')) {
            $model->setAttribute('is_deleted', true);
        }
        if (Schema::hasColumn($model->getTable(), 'deleted_by')) {
            $model->setAttribute('deleted_by', Auth::id());
        }
        if (Schema::hasColumn($model->getTable(), 'deleted_ip')) {
            $model->setAttribute('deleted_ip', request()?->ip());
        }
    }

    public function restored(Model $model): void
    {
        if ($this->shouldSkip($model)) {
            return;
        }

        $this->auditService->log('RESTORE_DATA', $model, [], $model->getAttributes(), $this->requestContext());
    }

    private function requestContext(): array
    {
        return [
            'location_status' => request('location_status'),
            'latitude' => request('latitude'),
            'longitude' => request('longitude'),
            'device_fingerprint' => request()->header('X-Device-Fingerprint'),
            'browser' => request()->header('X-Browser'),
            'os' => request()->header('X-OS'),
            'anomaly_flag' => (bool) request('anomaly_flag', false),
        ];
    }

    private function shouldSkip(Model $model): bool
    {
        return $model instanceof \App\Models\AuditLog;
    }
}
