<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendDiscordWebhookJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $title,
        public array $context = [],
        public string $level = 'info'
    ) {
    }

    public function handle(): void
    {
        $webhookUrl = (string) config('services.discord.webhook_url');
        if ($webhookUrl === '') {
            return;
        }

        $color = match (strtolower($this->level)) {
            'critical' => 0xE74C3C,
            'warning' => 0xF39C12,
            default => 0x2ECC71,
        };

        $fields = [];
        foreach ($this->context as $key => $value) {
            $fields[] = [
                'name' => (string) $key,
                'value' => is_scalar($value) ? (string) $value : json_encode($value),
                'inline' => false,
            ];
        }

        Http::timeout(8)->post($webhookUrl, [
            'embeds' => [[
                'title' => $this->title,
                'color' => $color,
                'fields' => $fields,
                'timestamp' => now()->toIso8601String(),
            ]],
        ]);
    }
}
