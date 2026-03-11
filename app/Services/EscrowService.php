<?php

namespace App\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\TutoringSession;
use Illuminate\Support\Facades\DB;

class EscrowService
{
    /**
     * State Machine:
     * 1. Booking Paid -> Hold Balance (Escrow)
     * 2. Session Complete -> Keep Held (Wait for dispute window)
     * 3. No Dispute / Dispute Resolved -> Release to Available Balance
     */

    public function holdFunds(Wallet $wallet, float $amount, TutoringSession $session)
    {
        return DB::transaction(function () use ($wallet, $amount, $session) {
            // Logic: Usually student pays to System Account, and we record a "Pending/Held" credit to Tentor's Wallet
            // But here we might be tracking Tentor's wallet directly.
            
            // Let's assume this is adding to Tentor's "Held Balance"
            $wallet->held_balance += $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'hold',
                'amount' => $amount,
                'balance_before' => $wallet->balance, // Available balance unchanged
                'balance_after' => $wallet->balance,
                'status' => 'success',
                'description' => "Escrow Hold for Session #{$session->id}",
                'reference_type' => get_class($session),
                'reference_id' => $session->id,
            ]);
        });
    }

    public function releaseFunds(Wallet $wallet, TutoringSession $session)
    {
        return DB::transaction(function () use ($wallet, $session) {
            // Find the original hold amount (simplified, in real app track exact transaction)
            // Or use session invoice amount
            $amount = $session->invoice ? $session->invoice->items->sum('amount') : 0; // Simplified

            if ($wallet->held_balance < $amount) {
                throw new \Exception("Insufficient held balance to release.");
            }

            $balanceBefore = $wallet->balance;
            
            // Move from Held to Available
            $wallet->held_balance -= $amount;
            $wallet->balance += $amount;
            $wallet->save();

            WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'type' => 'release',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'status' => 'success',
                'description' => "Funds Released for Session #{$session->id}",
                'reference_type' => get_class($session),
                'reference_id' => $session->id,
            ]);
            
            // Trigger Financial Service to recognize Revenue (if using platform fee model)
            // But here we release full amount to Tentor (assuming platform fee deducted elsewhere)
        });
    }

    public function disputeFunds(Wallet $wallet, TutoringSession $session)
    {
        // Move status to Under Review - Funds stay in Held Balance
        // Maybe flag the transaction or session
        $session->status = 'disputed';
        $session->save();
        
        // Log in Audit
    }
}
