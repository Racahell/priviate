<?php

namespace App\Observers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GeneralObserver
{
    /**
     * Handle the Model "created" event.
     */
    public function created(Model $model): void
    {
        $this->logAudit($model, 'created');
    }

    /**
     * Handle the Model "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->logAudit($model, 'updated');
    }

    /**
     * Handle the Model "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->logAudit($model, 'deleted');
    }

    protected function logAudit(Model $model, string $event)
    {
        // Don't log AuditLog itself to prevent recursion
        if ($model instanceof \App\Models\AuditLog) {
            return;
        }

        try {
            DB::table('audit_logs')->insert([
                'id' => (string) Str::uuid(),
                'user_id' => Auth::id(), // Can be null for system actions
                'event' => $event,
                'auditable_type' => get_class($model),
                'auditable_id' => $model->id,
                'old_values' => $event === 'created' ? null : json_encode($model->getOriginal()),
                'new_values' => $event === 'deleted' ? null : json_encode($model->getAttributes()),
                'url' => request()->fullUrl(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            // Fail silently or log to file to avoid blocking main transaction
            // Log::error("Audit Log Failed: " . $e->getMessage());
        }
    }
}
