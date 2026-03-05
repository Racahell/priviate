<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('registration_email_verifications')) {
            Schema::create('registration_email_verifications', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('token', 128)->unique();
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->string('sent_ip', 45)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('login_events')) {
            Schema::create('login_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('role')->nullable()->index();
                $table->string('status', 32)->index(); // LOGIN_SUCCESS / LOGIN_FAILED
                $table->string('session_id')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->decimal('latitude', 10, 8)->nullable();
                $table->decimal('longitude', 11, 8)->nullable();
                $table->string('location_status', 16)->default('DENIED');
                $table->string('device_fingerprint')->nullable();
                $table->string('browser')->nullable();
                $table->string('os')->nullable();
                $table->boolean('anomaly_flag')->default(false);
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('security_events')) {
            Schema::create('security_events', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('event_type', 64)->index();
                $table->string('severity', 16)->default('LOW')->index();
                $table->text('description')->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('menu_items')) {
            Schema::create('menu_items', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('label');
                $table->string('route_name')->nullable()->index();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('menu_permissions')) {
            Schema::create('menu_permissions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('menu_item_id')->index();
                $table->string('role_name')->index();
                $table->boolean('can_view')->default(true);
                $table->boolean('can_create')->default(false);
                $table->boolean('can_update')->default(false);
                $table->boolean('can_delete')->default(false);
                $table->timestamps();
                $table->unique(['menu_item_id', 'role_name']);
            });
        }

        if (!Schema::hasTable('packages')) {
            Schema::create('packages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('trial_enabled')->default(false);
                $table->unsignedInteger('trial_limit')->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('package_prices')) {
            Schema::create('package_prices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('package_id')->index();
                $table->decimal('price', 15, 2);
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('package_quotas')) {
            Schema::create('package_quotas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('package_id')->index();
                $table->unsignedInteger('quota')->default(0);
                $table->unsignedInteger('used_quota')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('schedule_slots')) {
            Schema::create('schedule_slots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('subject_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->unsignedBigInteger('tentor_id')->nullable()->index();
                $table->dateTime('start_at');
                $table->dateTime('end_at');
                $table->string('status', 32)->default('OPEN')->index();
                $table->timestamp('locked_at')->nullable();
                $table->timestamp('lock_expires_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('schedule_assignments')) {
            Schema::create('schedule_assignments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('schedule_slot_id')->index();
                $table->unsignedBigInteger('assigned_by')->nullable()->index();
                $table->unsignedBigInteger('tentor_id')->index();
                $table->string('assignment_mode', 16)->default('AUTO'); // AUTO/MANUAL
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('attendance_records')) {
            Schema::create('attendance_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tutoring_session_id')->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->unsignedBigInteger('student_id')->nullable()->index();
                $table->boolean('teacher_present')->default(false);
                $table->boolean('student_present')->default(false);
                $table->decimal('teacher_lat', 10, 8)->nullable();
                $table->decimal('teacher_lng', 11, 8)->nullable();
                $table->decimal('student_lat', 10, 8)->nullable();
                $table->decimal('student_lng', 11, 8)->nullable();
                $table->string('location_status', 16)->default('DENIED');
                $table->timestamp('attendance_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('material_reports')) {
            Schema::create('material_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tutoring_session_id')->index();
                $table->unsignedBigInteger('teacher_id')->nullable()->index();
                $table->text('summary');
                $table->text('homework')->nullable();
                $table->timestamp('submitted_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('disputes')) {
            Schema::create('disputes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tutoring_session_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->string('source_role', 32)->nullable();
                $table->string('reason', 64);
                $table->text('description')->nullable();
                $table->string('status', 32)->default('DISPUTE_OPEN')->index();
                $table->string('priority', 16)->default('MEDIUM');
                $table->timestamp('resolved_at')->nullable();
                $table->unsignedBigInteger('resolved_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('dispute_actions')) {
            Schema::create('dispute_actions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('dispute_id')->index();
                $table->unsignedBigInteger('actor_id')->nullable()->index();
                $table->string('action', 32);
                $table->text('notes')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('payroll_periods')) {
            Schema::create('payroll_periods', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->string('status', 16)->default('OPEN');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('teacher_payouts')) {
            Schema::create('teacher_payouts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payroll_period_id')->nullable()->index();
                $table->unsignedBigInteger('teacher_id')->index();
                $table->decimal('gross_amount', 15, 2)->default(0);
                $table->decimal('deduction_amount', 15, 2)->default(0);
                $table->decimal('net_amount', 15, 2)->default(0);
                $table->string('status', 16)->default('PENDING')->index(); // PENDING/PAID/FAILED
                $table->timestamp('paid_at')->nullable();
                $table->string('reference_number')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('parent_approvals')) {
            Schema::create('parent_approvals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('parent_id')->index();
                $table->string('context_type', 64);
                $table->unsignedBigInteger('context_id');
                $table->string('status', 16)->default('PENDING');
                $table->text('notes')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->index(['context_type', 'context_id']);
            });
        }

        if (!Schema::hasTable('reschedule_requests')) {
            Schema::create('reschedule_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tutoring_session_id')->index();
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->dateTime('requested_start_at')->nullable();
                $table->dateTime('requested_end_at')->nullable();
                $table->string('status', 16)->default('PENDING');
                $table->text('reason')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->unsignedBigInteger('approved_by')->nullable()->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('backup_jobs')) {
            Schema::create('backup_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('type', 32)->default('DB'); // DB/FILES/CONFIG
                $table->string('mode', 16)->default('UPDATE'); // UPDATE/FULL
                $table->string('file_path');
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->unsignedBigInteger('file_size')->default(0);
                $table->string('checksum_hash', 128)->nullable();
                $table->text('note')->nullable();
                $table->string('status', 16)->default('CREATED');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('restore_jobs')) {
            Schema::create('restore_jobs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('backup_job_id')->nullable()->index();
                $table->string('mode', 16)->default('PARTIAL'); // PARTIAL/DISASTER
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->string('status', 16)->default('PENDING');
                $table->json('diff_preview')->nullable();
                $table->text('reason')->nullable();
                $table->timestamp('executed_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('import_jobs')) {
            Schema::create('import_jobs', function (Blueprint $table) {
                $table->id();
                $table->string('type', 32); // users/items
                $table->unsignedBigInteger('requested_by')->nullable()->index();
                $table->string('status', 16)->default('PENDING');
                $table->unsignedInteger('total_rows')->default(0);
                $table->unsignedInteger('success_rows')->default(0);
                $table->unsignedInteger('failed_rows')->default(0);
                $table->text('error_summary')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('import_job_details')) {
            Schema::create('import_job_details', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('import_job_id')->index();
                $table->unsignedInteger('row_number');
                $table->string('status', 16)->default('SUCCESS');
                $table->text('message')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('items')) {
            Schema::create('items', function (Blueprint $table) {
                $table->id();
                $table->string('sku')->unique();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 15, 2)->default(0);
                $table->unsignedInteger('stock')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('password_reset_requests')) {
            Schema::create('password_reset_requests', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('email')->nullable()->index();
                $table->string('phone')->nullable()->index();
                $table->string('channel', 16); // EMAIL/WHATSAPP
                $table->string('otp_code', 16);
                $table->timestamp('expires_at');
                $table->timestamp('used_at')->nullable();
                $table->string('request_ip', 45)->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('web_settings')) {
            Schema::create('web_settings', function (Blueprint $table) {
                $table->id();
                $table->string('site_name')->nullable();
                $table->string('logo_url')->nullable();
                $table->text('address')->nullable();
                $table->string('manager_name')->nullable();
                $table->string('contact_email')->nullable();
                $table->string('contact_phone')->nullable();
                $table->json('extra')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('financial_reports')) {
            Schema::create('financial_reports', function (Blueprint $table) {
                $table->id();
                $table->date('report_date')->index();
                $table->decimal('revenue', 15, 2)->default(0);
                $table->decimal('teacher_payout', 15, 2)->default(0);
                $table->decimal('refund', 15, 2)->default(0);
                $table->decimal('operational_cost', 15, 2)->default(0);
                $table->decimal('escrow_outstanding', 15, 2)->default(0);
                $table->decimal('net_profit', 15, 2)->default(0);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('operational_cost_entries')) {
            Schema::create('operational_cost_entries', function (Blueprint $table) {
                $table->id();
                $table->date('cost_date')->index();
                $table->string('category');
                $table->decimal('amount', 15, 2);
                $table->text('description')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();
            });
        }

        $this->expandAuditLogs();
        $this->addGovernanceColumns();
    }

    public function down(): void
    {
        // Intentionally keep data-safe rollback behavior for enterprise tables.
        // Dropping all tables here can be destructive for production backups.
    }

    private function expandAuditLogs(): void
    {
        if (!Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::table('audit_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('audit_logs', 'session_id')) {
                $table->string('session_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('audit_logs', 'role')) {
                $table->string('role', 32)->nullable()->after('user_id');
            }
            if (!Schema::hasColumn('audit_logs', 'action')) {
                $table->string('action', 64)->nullable()->after('event');
            }
            if (!Schema::hasColumn('audit_logs', 'location_status')) {
                $table->string('location_status', 16)->nullable()->after('ip_address');
            }
            if (!Schema::hasColumn('audit_logs', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('location_status');
            }
            if (!Schema::hasColumn('audit_logs', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('audit_logs', 'device_fingerprint')) {
                $table->string('device_fingerprint')->nullable()->after('user_agent');
            }
            if (!Schema::hasColumn('audit_logs', 'browser')) {
                $table->string('browser')->nullable()->after('device_fingerprint');
            }
            if (!Schema::hasColumn('audit_logs', 'os')) {
                $table->string('os')->nullable()->after('browser');
            }
            if (!Schema::hasColumn('audit_logs', 'anomaly_flag')) {
                $table->boolean('anomaly_flag')->default(false)->after('os');
            }
            if (!Schema::hasColumn('audit_logs', 'checksum_signature')) {
                $table->string('checksum_signature', 64)->nullable()->after('anomaly_flag');
            }
        });
    }

    private function addGovernanceColumns(): void
    {
        $tables = [
            'users',
            'invoices',
            'invoice_items',
            'payments',
            'subjects',
            'tentor_profiles',
            'siswa_profiles',
            'tentor_skills',
            'tentor_availabilities',
            'tutoring_sessions',
            'wallets',
            'wallet_transactions',
            'withdrawals',
            'coas',
            'accounting_periods',
            'financial_ledgers',
            'journal_entries',
            'journal_items',
            'fraud_logs',
            'system_settings',
            'tenants',
            'user_consents',
            'menu_items',
            'menu_permissions',
            'packages',
            'package_prices',
            'package_quotas',
            'schedule_slots',
            'schedule_assignments',
            'attendance_records',
            'material_reports',
            'disputes',
            'dispute_actions',
            'teacher_payouts',
            'payroll_periods',
            'parent_approvals',
            'reschedule_requests',
            'backup_jobs',
            'restore_jobs',
            'import_jobs',
            'import_job_details',
            'items',
            'web_settings',
            'financial_reports',
            'operational_cost_entries',
        ];

        foreach ($tables as $tableName) {
            if (!Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (!Schema::hasColumn($tableName, 'created_by')) {
                    $table->unsignedBigInteger('created_by')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'updated_by')) {
                    $table->unsignedBigInteger('updated_by')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'deleted_by')) {
                    $table->unsignedBigInteger('deleted_by')->nullable()->index();
                }
                if (!Schema::hasColumn($tableName, 'created_ip')) {
                    $table->string('created_ip', 45)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'updated_ip')) {
                    $table->string('updated_ip', 45)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'deleted_ip')) {
                    $table->string('deleted_ip', 45)->nullable();
                }
                if (!Schema::hasColumn($tableName, 'is_deleted')) {
                    $table->boolean('is_deleted')->default(false)->index();
                }
                if (!Schema::hasColumn($tableName, 'deleted_at')) {
                    $table->timestamp('deleted_at')->nullable()->index();
                }
            });
        }
    }
};
