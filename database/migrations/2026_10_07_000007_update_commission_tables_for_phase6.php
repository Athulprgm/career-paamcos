<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // ─── 1. Update commission_rules Table ──────────────────────────────────
        Schema::table('commission_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_rules', 'commission_type')) {
                $table->string('commission_type', 30)->default('PERCENTAGE')->after('code');
            }
            if (! Schema::hasColumn('commission_rules', 'calculation_basis')) {
                $table->string('calculation_basis', 50)->default('COLLECTION_AMOUNT')->after('commission_type');
            }
            if (! Schema::hasColumn('commission_rules', 'rate')) {
                $table->decimal('rate', 12, 2)->default(0.00)->after('calculation_basis');
            }
        });

        // Normalize existing rules
        if (Schema::hasColumn('commission_rules', 'fixed_amount') && Schema::hasColumn('commission_rules', 'commission_percentage')) {
            DB::statement("UPDATE commission_rules SET commission_type = CASE WHEN fixed_amount > 0 THEN 'FIXED' ELSE 'PERCENTAGE' END WHERE commission_type IS NULL OR commission_type = ''");
            DB::statement('UPDATE commission_rules SET rate = CASE WHEN fixed_amount > 0 THEN fixed_amount ELSE commission_percentage END WHERE rate = 0 OR rate IS NULL');
        }
        if (Schema::hasColumn('commission_rules', 'calculation_type')) {
            DB::statement("UPDATE commission_rules SET calculation_basis = 'DEPOSIT_AMOUNT' WHERE LOWER(calculation_type) = 'deposit_amount'");
            DB::statement("UPDATE commission_rules SET calculation_basis = 'COLLECTION_AMOUNT' WHERE LOWER(calculation_type) = 'collection_amount' OR calculation_basis IS NULL");
        }
        DB::statement('UPDATE commission_rules SET status = UPPER(status)');

        // ─── 2. Update commission_ledgers Table ────────────────────────────────
        Schema::table('commission_ledgers', function (Blueprint $table) {
            if (! Schema::hasColumn('commission_ledgers', 'commission_number')) {
                $table->string('commission_number', 30)->nullable()->after('commission_id');
            }
            if (! Schema::hasColumn('commission_ledgers', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('branch_id')->constrained('customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('commission_ledgers', 'deposit_collection_id')) {
                $table->foreignId('deposit_collection_id')->nullable()->after('deposit_id')->constrained('deposit_collections')->nullOnDelete();
            }
            if (! Schema::hasColumn('commission_ledgers', 'collection_payment_id')) {
                $table->foreignId('collection_payment_id')->nullable()->after('deposit_collection_id')->constrained('collection_payments')->nullOnDelete();
            }
            if (! Schema::hasColumn('commission_ledgers', 'period')) {
                $table->string('period', 20)->nullable()->after('status');
            }
            if (! Schema::hasColumn('commission_ledgers', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('paid_at');
            }
            if (! Schema::hasColumn('commission_ledgers', 'payment_method')) {
                $table->string('payment_method', 50)->nullable()->after('payment_date');
            }
            if (! Schema::hasColumn('commission_ledgers', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('payment_method')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('commission_ledgers', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('commission_ledgers', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('commission_ledgers', 'notes')) {
                $table->text('notes')->nullable()->after('payout_remarks');
            }
            if (! Schema::hasColumn('commission_ledgers', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('commission_ledgers', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Sync commission_number and status
        DB::statement('UPDATE commission_ledgers SET commission_number = commission_id WHERE commission_number IS NULL');
        DB::statement('UPDATE commission_ledgers SET status = UPPER(status)');
        DB::statement("UPDATE commission_ledgers SET period = DATE_FORMAT(created_at, '%Y-%m') WHERE period IS NULL AND created_at IS NOT NULL");

        // Backfill customer_id from deposits if null
        DB::statement('UPDATE commission_ledgers cl JOIN deposits d ON cl.deposit_id = d.id SET cl.customer_id = d.customer_id WHERE cl.customer_id IS NULL');

        // Add useful composite indexes
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_comm_branch_status');
            $table->index(['agent_id', 'status'], 'idx_comm_agent_status');
            $table->index(['period', 'status'], 'idx_comm_period_status');
            $table->index('commission_number', 'idx_comm_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('commission_ledgers', function (Blueprint $table) {
            $table->dropIndex('idx_comm_branch_status');
            $table->dropIndex('idx_comm_agent_status');
            $table->dropIndex('idx_comm_period_status');
            $table->dropIndex('idx_comm_number');

            $table->dropForeign(['created_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['collection_payment_id']);
            $table->dropForeign(['deposit_collection_id']);
            $table->dropForeign(['customer_id']);

            $table->dropColumn([
                'commission_number',
                'customer_id',
                'deposit_collection_id',
                'collection_payment_id',
                'period',
                'payment_date',
                'payment_method',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'notes',
                'created_by',
                'deleted_at',
            ]);
        });

        Schema::table('commission_rules', function (Blueprint $table) {
            $table->dropColumn([
                'commission_type',
                'calculation_basis',
                'rate',
            ]);
        });
    }
};
