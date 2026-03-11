<?php

namespace App\Mail;

use App\Models\Dispute;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DisputeReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Dispute $dispute,
        public string $notes,
        public string $status,
        public ?string $actorName = null,
        public ?string $recipientName = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Balasan Kritik #' . $this->dispute->id
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.dispute-reply'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
