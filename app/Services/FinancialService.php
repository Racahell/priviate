<?php

namespace App\Services;

use App\Models\JournalEntry;
use App\Models\JournalItem;
use App\Models\Coa;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class FinancialService
{
    /**
     * Record a Double-Entry Transaction
     * 
     * @param string $description
     * @param array $items Array of ['coa_code' => '...', 'debit' => 0, 'credit' => 0]
     * @param Model|null $reference
     * @param string $date Y-m-d
     * @return JournalEntry
     * @throws \Exception
     */
    public function recordTransaction(string $description, array $items, ?Model $reference = null, string $date = null)
    {
        return DB::transaction(function () use ($description, $items, $reference, $date) {
            // 1. Validate Balance
            $totalDebit = collect($items)->sum('debit');
            $totalCredit = collect($items)->sum('credit');

            if (abs($totalDebit - $totalCredit) > 0.01) {
                throw new \Exception("Journal Entry is not balanced. Debit: $totalDebit, Credit: $totalCredit");
            }

            // 2. Create Header
            $entry = JournalEntry::create([
                'transaction_date' => $date ?? now()->toDateString(),
                'description' => $description,
                'reference_type' => $reference ? get_class($reference) : null,
                'reference_id' => $reference ? $reference->id : null,
                'is_locked' => false, // Check period locking logic here if needed
            ]);

            // 3. Create Items
            foreach ($items as $item) {
                // Resolve CoA ID from Code
                $coa = Coa::where('code', $item['coa_code'])->firstOrFail();

                JournalItem::create([
                    'journal_entry_id' => $entry->id,
                    'coa_id' => $coa->id,
                    'debit' => $item['debit'] ?? 0,
                    'credit' => $item['credit'] ?? 0,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Deferred Revenue Logic: Move from Liability to Revenue
     */
    public function recognizeRevenue(Model $session)
    {
        // Example: Move 100,000 from '201' (Unearned Revenue) to '401' (Service Revenue)
        $amount = $session->invoice->items->sum('amount'); // Simplified

        $items = [
            [
                'coa_code' => '201', // Unearned Revenue (Liability) - Debit to decrease
                'debit' => $amount,
                'credit' => 0,
            ],
            [
                'coa_code' => '401', // Service Revenue (Income) - Credit to increase
                'debit' => 0,
                'credit' => $amount,
            ],
        ];

        return $this->recordTransaction(
            "Revenue Recognition for Session #{$session->id}",
            $items,
            $session
        );
    }
}
