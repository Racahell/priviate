<?php

namespace App\Observers;

use App\Models\TutoringSession;
use App\Services\EscrowService;
use Illuminate\Support\Facades\Log;

class TutoringSessionObserver
{
    protected $escrowService;

    public function __construct(EscrowService $escrowService)
    {
        $this->escrowService = $escrowService;
    }

    /**
     * Handle the TutoringSession "updated" event.
     */
    public function updated(TutoringSession $tutoringSession): void
    {
        // Check if rating was just added and is low (< 3)
        if ($tutoringSession->isDirty('rating') && $tutoringSession->rating !== null) {
            if ($tutoringSession->rating < 3) {
                $this->handleLowRating($tutoringSession);
            }
        }
    }

    protected function handleLowRating(TutoringSession $session)
    {
        Log::channel('financial')->warning("Low rating detected for Session #{$session->id}. Initiating Dispute.");

        // 1. Mark as Disputed
        $session->status = 'disputed';
        $session->saveQuietly(); // Prevent infinite loop

        // 2. Trigger Escrow Dispute (Hold Funds)
        if ($session->tentor && $session->tentor->wallet) {
            $this->escrowService->disputeFunds($session->tentor->wallet, $session);
        }

        // 3. Create Dispute Ticket (Mock)
        // Dispute::create([...]);
    }
}
