<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class SendWhatsappMessageJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $target,
        public string $message
    ) {
    }

    public function handle(): void
    {
        $token = (string) config('services.fonnte.token');
        if ($token === '' || $this->target === '') {
            return;
        }

        Http::withHeaders([
            'Authorization' => $token,
        ])->asForm()->post((string) config('services.fonnte.endpoint'), [
            'target' => $this->target,
            'message' => $this->message,
        ]);
    }
}
