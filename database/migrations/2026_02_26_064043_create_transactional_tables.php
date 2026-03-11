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
        // 1. Invoices (Immutable Transaction)
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique(); // Auto-sequence
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Student
            $table->decimal('total_amount', 15, 2);
            $table->enum('status', ['unpaid', 'paid', 'partially_paid', 'cancelled', 'overdue'])->default('unpaid');
            $table->date('issue_date');
            $table->date('due_date');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes(); // For audit, but logically immutable
        });

        // 2. Invoice Items
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('description');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('amount', 15, 2); // quantity * unit_price
            $table->timestamps();
        });

        // 3. Payments (Separated from Invoices)
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->onDelete('cascade');
            $table->string('payment_method'); // e.g., 'bank_transfer', 'credit_card'
            $table->string('transaction_id')->nullable(); // From Payment Gateway
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'success', 'failed', 'refunded'])->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->string('proof_url')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
