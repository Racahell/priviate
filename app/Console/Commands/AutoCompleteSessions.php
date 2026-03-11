<?php

namespace App\Console\Commands;

use App\Models\TutoringSession;
use App\Services\EscrowService;
use App\Services\FinancialService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCompleteSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'session:auto-complete';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auto-complete sessions after 48 hours and release funds';

    /**
     * Execute the console command.
     */
    public function handle(EscrowService $escrowService, FinancialService $financialService)
    {
        $this->info('Scanning for pending completion sessions...');

        // Find sessions that ended > 48 hours ago and are still 'ongoing' or 'awaiting_rating'
        // Assuming 'completed' status means manually completed/rated
        // Let's say status flow: booked -> ongoing -> completed (by user) OR auto-completed
        
        $cutoff = now()->subHours(48);

        $sessions = TutoringSession::whereIn('status', ['ongoing', 'awaiting_rating'])
            ->where('check_out_time', '<=', $cutoff)
            ->get();

        $count = 0;

        foreach ($sessions as $session) {
            DB::beginTransaction();
            try {
                // 1. Update Status
                $session->status = 'auto_completed';
                $session->auto_completed_at = now();
                $session->save();

                // 2. Release Funds (Escrow)
                // Assuming wallet relation exists on Tentor
                if ($session->tentor && $session->tentor->wallet) {
                    $escrowService->releaseFunds($session->tentor->wallet, $session);
                }

                // 3. Recognize Revenue
                $financialService->recognizeRevenue($session);

                DB::commit();
                $count++;
                $this->info("Session #{$session->id} auto-completed.");
                
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Auto-complete failed for session #{$session->id}: " . $e->getMessage());
                $this->error("Failed to process Session #{$session->id}");
            }
        }

        $this->info("Processed {$count} sessions.");
    }
}
