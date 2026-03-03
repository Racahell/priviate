<?php

namespace App\Services;

use App\Jobs\SendDiscordWebhookJob;
use Illuminate\Support\Facades\Log;

class DiscordAlertService
{
    public function send(string $title, array $context = [], string $level = 'info'): void
    {
        $webhookUrl = config('services.discord.webhook_url');
        if (empty($webhookUrl)) {
            return;
        }

        try {
            SendDiscordWebhookJob::dispatch($title, $context, $level);
        } catch (\Throwable $e) {
            Log::warning('Failed to send Discord alert', ['error' => $e->getMessage()]);
        }
    }
}
