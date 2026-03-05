<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendRawEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $to,
        public string $subject,
        public string $body
    ) {
    }

    public function handle(): void
    {
        Mail::raw($this->body, function ($message) {
            $message->to($this->to)->subject($this->subject);
        });
    }
}
