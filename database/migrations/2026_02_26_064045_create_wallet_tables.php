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
        // 1. Wallets
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0); // Available for withdrawal
            $table->decimal('held_balance', 15, 2)->default(0); // Escrow / Pending clearance
            $table->string('pin')->nullable(); // For security
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Wallet Transactions (Audit Trail for Money)
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'payment', 'payout', 'hold', 'release', 'penalty', 'refund']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed'])->default('success');
            $table->string('description');
            $table->nullableMorphs('reference'); // Linked to Session, Invoice, Withdrawal
            $table->timestamps();
        });

        // 3. Withdrawals (Payout Requests)
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->string('bank_name');
            $table->string('account_number');
            $table->string('account_holder');
            $table->enum('status', ['requested', 'processing', 'completed', 'rejected'])->default('requested');
            $table->text('admin_note')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdrawals');
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
