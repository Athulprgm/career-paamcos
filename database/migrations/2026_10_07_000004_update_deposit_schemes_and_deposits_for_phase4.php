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
        // ─── 1. Update deposit_schemes table ──────────────────────────────────
        Schema::table('deposit_schemes', function (Blueprint $table) {
            if (! Schema::hasColumn('deposit_schemes', 'tenure')) {
                $table->unsignedInteger('tenure')->default(12)->after('max_amount');
            }
            if (! Schema::hasColumn('deposit_schemes', 'tenure_unit')) {
                $table->string('tenure_unit', 20)->default('MONTHS')->after('tenure');
            }
            if (! Schema::hasColumn('deposit_schemes', 'interest_rate')) {
                $table->decimal('interest_rate', 8, 2)->default(0.00)->after('tenure_unit');
            }
            if (! Schema::hasColumn('deposit_schemes', 'collection_frequency')) {
                $table->string('collection_frequency', 50)->default('ONE_TIME')->after('interest_rate');
            }
            if (! Schema::hasColumn('deposit_schemes', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposit_schemes', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });

        // Normalize existing scheme records
        if (Schema::hasColumn('deposit_schemes', 'deposit_frequency')) {
            DB::statement("UPDATE deposit_schemes SET collection_frequency = UPPER(deposit_frequency) WHERE (collection_frequency IS NULL OR collection_frequency = 'ONE_TIME') AND deposit_frequency IS NOT NULL");
        }
        DB::statement('UPDATE deposit_schemes SET status = UPPER(status)');

        // ─── 2. Update deposits table ─────────────────────────────────────────
        Schema::table('deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('deposits', 'deposit_number')) {
                $table->string('deposit_number', 30)->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('deposits', 'customer_id')) {
                $table->foreignId('customer_id')->nullable()->after('deposit_number')->constrained('customers')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposits', 'start_date')) {
                $table->date('start_date')->nullable()->after('amount');
            }
            if (! Schema::hasColumn('deposits', 'tenure')) {
                $table->unsignedInteger('tenure')->default(12)->after('start_date');
            }
            if (! Schema::hasColumn('deposits', 'tenure_unit')) {
                $table->string('tenure_unit', 20)->default('MONTHS')->after('tenure');
            }
            if (! Schema::hasColumn('deposits', 'collection_frequency')) {
                $table->string('collection_frequency', 50)->default('ONE_TIME')->after('tenure_unit');
            }
            if (! Schema::hasColumn('deposits', 'interest_rate')) {
                $table->decimal('interest_rate', 8, 2)->nullable()->after('collection_frequency');
            }
            if (! Schema::hasColumn('deposits', 'maturity_date')) {
                $table->date('maturity_date')->nullable()->after('interest_rate');
            }
            if (! Schema::hasColumn('deposits', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposits', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('deposits', 'rejected_by')) {
                $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposits', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            }
            if (! Schema::hasColumn('deposits', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('rejected_at');
            }
            if (! Schema::hasColumn('deposits', 'activated_at')) {
                $table->timestamp('activated_at')->nullable()->after('rejection_reason');
            }
            if (! Schema::hasColumn('deposits', 'matured_at')) {
                $table->timestamp('matured_at')->nullable()->after('activated_at');
            }
            if (! Schema::hasColumn('deposits', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('matured_at');
            }
            if (! Schema::hasColumn('deposits', 'closed_by')) {
                $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('deposits', 'notes')) {
                $table->text('notes')->nullable()->after('closed_by');
            }
        });

        // Backfill existing sample deposits if columns exist
        if (Schema::hasColumn('deposits', 'deposit_id') && Schema::hasColumn('deposits', 'deposit_number')) {
            DB::statement('UPDATE deposits SET deposit_number = deposit_id WHERE deposit_number IS NULL');
        }
        if (Schema::hasColumn('deposits', 'deposit_date') && Schema::hasColumn('deposits', 'start_date')) {
            DB::statement('UPDATE deposits SET start_date = deposit_date WHERE start_date IS NULL');
        }
        if (Schema::hasColumn('deposits', 'remarks') && Schema::hasColumn('deposits', 'notes')) {
            DB::statement('UPDATE deposits SET notes = remarks WHERE notes IS NULL');
        }
        if (Schema::hasColumn('deposits', 'verified_by') && Schema::hasColumn('deposits', 'approved_by')) {
            DB::statement('UPDATE deposits SET approved_by = verified_by WHERE approved_by IS NULL AND verified_by IS NOT NULL');
        }
        if (Schema::hasColumn('deposits', 'verified_at') && Schema::hasColumn('deposits', 'approved_at')) {
            DB::statement('UPDATE deposits SET approved_at = verified_at WHERE approved_at IS NULL AND verified_at IS NOT NULL');
        }

        // Normalize status strings in existing rows
        DB::statement("UPDATE deposits SET status = 'APPROVED' WHERE LOWER(status) = 'approved'");
        DB::statement("UPDATE deposits SET status = 'PENDING_APPROVAL' WHERE LOWER(status) = 'pending'");
        DB::statement("UPDATE deposits SET status = 'REJECTED' WHERE LOWER(status) = 'rejected'");
        DB::statement("UPDATE deposits SET status = UPPER(status) WHERE status NOT IN ('APPROVED', 'PENDING_APPROVAL', 'REJECTED')");

        // Compute maturity date for existing rows if null
        DB::statement('UPDATE deposits SET maturity_date = DATE_ADD(start_date, INTERVAL 12 MONTH) WHERE maturity_date IS NULL AND start_date IS NOT NULL');

        // Add useful composite indexes
        Schema::table('deposits', function (Blueprint $table) {
            $table->index(['branch_id', 'status'], 'idx_deposits_branch_status');
            $table->index(['customer_id', 'status'], 'idx_deposits_customer_status');
            $table->index(['agent_id', 'status'], 'idx_deposits_agent_status');
            $table->index(['deposit_scheme_id', 'status'], 'idx_deposits_scheme_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $table->dropIndex('idx_deposits_branch_status');
            $table->dropIndex('idx_deposits_customer_status');
            $table->dropIndex('idx_deposits_agent_status');
            $table->dropIndex('idx_deposits_scheme_status');

            $table->dropForeign(['closed_by']);
            $table->dropForeign(['rejected_by']);
            $table->dropForeign(['approved_by']);
            $table->dropForeign(['customer_id']);

            $table->dropColumn([
                'deposit_number',
                'customer_id',
                'start_date',
                'tenure',
                'tenure_unit',
                'collection_frequency',
                'interest_rate',
                'maturity_date',
                'approved_by',
                'approved_at',
                'rejected_by',
                'rejected_at',
                'rejection_reason',
                'activated_at',
                'matured_at',
                'closed_at',
                'closed_by',
                'notes',
            ]);
        });

        Schema::table('deposit_schemes', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'tenure',
                'tenure_unit',
                'interest_rate',
                'collection_frequency',
                'created_by',
            ]);
        });
    }
};
