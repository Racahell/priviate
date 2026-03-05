<?php

namespace App\Repositories;

use App\Models\Invoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class InvoiceRepository
{
    /**
     * Generate Unique Invoice Number with Locking Mechanism
     * Format: SIM-LP/{YEAR}/{MONTH}/XXXXX
     */
    public function generateInvoiceNumber()
    {
        return DB::transaction(function () {
            // Lock the invoices table or a sequence table to ensure unique number
            // Here we use a simpler approach: Lock the latest invoice record for update
            
            $now = Carbon::now();
            $year = $now->format('Y');
            $month = $now->format('m');
            $prefix = "SIM-LP/{$year}/{$month}/";

            // Get the last invoice number for this month
            // FOR UPDATE prevents race conditions
            $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}%")
                ->orderBy('id', 'desc')
                ->lockForUpdate()
                ->first();

            if (!$lastInvoice) {
                $nextSequence = 1;
            } else {
                // Extract sequence number
                $lastNumber = substr($lastInvoice->invoice_number, strrpos($lastInvoice->invoice_number, '/') + 1);
                $nextSequence = intval($lastNumber) + 1;
            }

            // Pad with zeros (e.g., 00001)
            $sequenceString = str_pad($nextSequence, 5, '0', STR_PAD_LEFT);
            
            return "{$prefix}{$sequenceString}";
        });
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data['invoice_number'] = $this->generateInvoiceNumber();
            return Invoice::create($data);
        });
    }

    public function find($id)
    {
        return Invoice::findOrFail($id);
    }
}
