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
        Schema::table('deposit_schemes', function (Blueprint $table) {
            if (! Schema::hasColumn('deposit_schemes', 'interest_payout_frequency')) {
                $table->string('interest_payout_frequency', 50)->default('AT_MATURITY')->after('interest_rate');
            }
            if (! Schema::hasColumn('deposit_schemes', 'senior_citizen_extra_rate')) {
                $table->decimal('senior_citizen_extra_rate', 5, 2)->default(0.00)->after('interest_payout_frequency');
            }
            if (! Schema::hasColumn('deposit_schemes', 'lock_in_months')) {
                $table->unsignedSmallInteger('lock_in_months')->default(0)->after('tenure_unit');
            }
            if (! Schema::hasColumn('deposit_schemes', 'allow_premature_withdrawal')) {
                $table->boolean('allow_premature_withdrawal')->default(true)->after('lock_in_months');
            }
            if (! Schema::hasColumn('deposit_schemes', 'premature_penalty_percent')) {
                $table->decimal('premature_penalty_percent', 5, 2)->default(1.00)->after('allow_premature_withdrawal');
            }
            if (! Schema::hasColumn('deposit_schemes', 'allow_loan_against_deposit')) {
                $table->boolean('allow_loan_against_deposit')->default(true)->after('premature_penalty_percent');
            }
            if (! Schema::hasColumn('deposit_schemes', 'max_loan_percentage')) {
                $table->decimal('max_loan_percentage', 5, 2)->default(80.00)->after('allow_loan_against_deposit');
            }
            if (! Schema::hasColumn('deposit_schemes', 'grace_period_days')) {
                $table->unsignedSmallInteger('grace_period_days')->default(7)->after('collection_frequency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_schemes', function (Blueprint $table) {
            $cols = [
                'interest_payout_frequency',
                'senior_citizen_extra_rate',
                'lock_in_months',
                'allow_premature_withdrawal',
                'premature_penalty_percent',
                'allow_loan_against_deposit',
                'max_loan_percentage',
                'grace_period_days',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('deposit_schemes', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
