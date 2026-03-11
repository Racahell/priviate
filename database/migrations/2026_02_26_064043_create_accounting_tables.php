<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Chart of Accounts (CoA)
        Schema::create('coas', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., '101'
            $table->string('name'); // e.g., 'Cash', 'Accounts Receivable'
            $table->enum('type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Accounting Periods (for Period Locking)
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., 'January 2026'
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_closed')->default(false);
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        // 3. Financial Ledger (Double-Entry)
        Schema::create('financial_ledgers', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->foreignId('coa_id')->constrained('coas');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description');
            $table->nullableMorphs('reference'); // Polymorphic relation to Invoice, Payment, etc.
            $table->foreignId('accounting_period_id')->nullable()->constrained('accounting_periods');
            $table->timestamps();
            
            // Index for querying
            $table->index(['coa_id', 'transaction_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_ledgers');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('coas');
    }
};
