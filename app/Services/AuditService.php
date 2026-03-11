<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    public function __construct(private readonly DiscordAlertService $discordAlertService)
    {
    }

    public function log(
        string $action,
        ?Model $model = null,
        array $oldValues = [],
        array $newValues = [],
        array $context = []
    ): void {
        if (!DB::getSchemaBuilder()->hasTable('audit_logs')) {
            return;
        }

        $user = Auth::user();
        $request = request();
        $sessionId = null;
        if ($request && method_exists($request, 'hasSession') && $request->hasSession()) {
            $sessionId = $request->session()->getId();
        }

        $payload = [
            'session_id' => $sessionId,
            'user_id' => $user?->id,
            'role' => $user?->getRoleNames()->first(),
            'event' => strtolower($action),
            'action' => $action,
            'auditable_type' => $model ? get_class($model) : ($context['auditable_type'] ?? null),
            'auditable_id' => $model?->getKey() ?? ($context['auditable_id'] ?? null),
            'old_values' => empty($oldValues) ? null : json_encode($oldValues),
            'new_values' => empty($newValues) ? null : json_encode($newValues),
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'location_status' => $context['location_status'] ?? null,
            'latitude' => $context['latitude'] ?? null,
            'longitude' => $context['longitude'] ?? null,
            'user_agent' => $request?->userAgent(),
            'device_fingerprint' => $context['device_fingerprint'] ?? null,
            'browser' => $context['browser'] ?? null,
            'os' => $context['os'] ?? null,
            'anomaly_flag' => (bool) ($context['anomaly_flag'] ?? false),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $payload['checksum_signature'] = hash('sha256', json_encode($payload));

        DB::table('audit_logs')->insert(array_merge(
            ['id' => (string) Str::uuid()],
            $payload
        ));

        if ((bool) env('DISCORD_ALERT_ON_UPDATE_DELETE', true) && in_array($action, ['UPDATE', 'SOFT_DELETE', 'HARD_DELETE'], true)) {
            $this->discordAlertService->send('User Data Change Alert', [
                'action' => $action,
                'actor_id' => $user?->id ?? 'SYSTEM',
                'role' => $user?->getRoleNames()->first(),
                'table' => $model ? class_basename($model) : 'N/A',
                'record_id' => $model?->getKey(),
                'ip' => $request?->ip(),
                'time' => now()->toDateTimeString(),
            ], 'warning');
        }
    }
}
