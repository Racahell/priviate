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
        // 1. Whitelabeling / Tenants
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('domain')->unique(); // e.g., 'bimbel-a.com'
            $table->string('name');
            $table->string('logo_url')->nullable();
            $table->string('primary_color')->default('#007bff');
            $table->text('footer_content')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Consent Tracking
        Schema::create('user_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('tos_version'); // e.g., 'v1.0'
            $table->timestamp('agreed_at');
            $table->timestamps();
        });

        // 3. Double-Entry Journal (Header)
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->string('description');
            $table->nullableMorphs('reference'); // Invoice, Payment, Withdrawal
            $table->string('currency', 3)->default('IDR');
            $table->boolean('is_locked')->default(false); // Period Locking
            $table->foreignId('tenant_id')->nullable()->constrained(); // Multi-tenant support
            $table->timestamps();
        });

        // 4. Double-Entry Journal Items (Lines)
        Schema::create('journal_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained()->onDelete('cascade');
            $table->foreignId('coa_id')->constrained('coas'); // Chart of Account
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->timestamps();
        });

        // 5. Fraud Logs
        Schema::create('fraud_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The suspect
            $table->string('type'); // e.g., 'geofence_mismatch', 'excessive_cancellation'
            $table->text('description')->nullable();
            $table->integer('severity_score')->default(0); // 0-100
            $table->json('metadata')->nullable(); // Context: lat/lng, session_id
            $table->timestamps();
        });

        // 6. Idempotency Keys
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('path');
            $table->string('method');
            $table->integer('response_code')->nullable();
            $table->longText('response_body')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('fraud_logs');
        Schema::dropIfExists('journal_items');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('user_consents');
        Schema::dropIfExists('tenants');
    }
};
