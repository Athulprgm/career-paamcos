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
        Schema::table('deposit_scheme_interest_rates', function (Blueprint $table) {
            if (! Schema::hasColumn('deposit_scheme_interest_rates', 'effective_from')) {
                $table->date('effective_from')->default('2020-01-01')->after('interest_rate');
            }
            if (! Schema::hasColumn('deposit_scheme_interest_rates', 'effective_to')) {
                $table->date('effective_to')->nullable()->after('effective_from');
            }
            if (! Schema::hasColumn('deposit_scheme_interest_rates', 'calculation_method')) {
                $table->string('calculation_method', 50)->nullable()->after('status');
            }
            if (! Schema::hasColumn('deposit_scheme_interest_rates', 'description') && ! Schema::hasColumn('deposit_scheme_interest_rates', 'notes')) {
                $table->text('description')->nullable();
            }
            if (! Schema::hasColumn('deposit_scheme_interest_rates', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }
        });

        $existingIndexes = collect(DB::select('SHOW INDEXES FROM deposit_scheme_interest_rates'))->pluck('Key_name')->unique()->all();

        if (in_array('ds_interest_rates_unique', $existingIndexes, true)) {
            Schema::table('deposit_scheme_interest_rates', function (Blueprint $table) {
                $table->dropUnique('ds_interest_rates_unique');
            });
        }

        Schema::table('deposit_scheme_interest_rates', function (Blueprint $table) use ($existingIndexes) {
            if (! in_array('ds_rates_scheme_tenure_idx', $existingIndexes, true)) {
                $table->index(['deposit_scheme_id', 'tenure', 'tenure_unit'], 'ds_rates_scheme_tenure_idx');
            }
            if (! in_array('ds_rates_effective_dates_idx', $existingIndexes, true)) {
                $table->index(['effective_from', 'effective_to'], 'ds_rates_effective_dates_idx');
            }
        });

        Schema::table('deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('deposits', 'deposit_interest_rate_id')) {
                $table->foreignId('deposit_interest_rate_id')
                    ->nullable()
                    ->after('deposit_scheme_id')
                    ->constrained('deposit_scheme_interest_rates')
                    ->nullOnDelete();
            }
        });

        // Seed initial rates for existing deposit schemes if table is empty
        if (Schema::hasTable('deposit_schemes') && DB::table('deposit_scheme_interest_rates')->count() === 0) {
            $schemes = DB::table('deposit_schemes')->get();
            foreach ($schemes as $scheme) {
                if (! empty($scheme->tenure) && isset($scheme->interest_rate)) {
                    DB::table('deposit_scheme_interest_rates')->insert([
                        'deposit_scheme_id' => $scheme->id,
                        'tenure' => (int) $scheme->tenure,
                        'tenure_unit' => strtoupper(trim((string) ($scheme->tenure_unit ?? 'MONTHS'))),
                        'interest_rate' => $scheme->interest_rate,
                        'senior_citizen_extra_rate' => $scheme->senior_citizen_extra_rate ?? 0.00,
                        'min_amount' => $scheme->min_amount,
                        'max_amount' => $scheme->max_amount,
                        'effective_from' => ! empty($scheme->created_at) ? substr((string) $scheme->created_at, 0, 10) : '2020-01-01',
                        'effective_to' => null,
                        'status' => strtoupper((string) ($scheme->status ?? 'ACTIVE')) === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
                        'calculation_method' => null,
                        'notes' => 'Initial default rate tier for scheme',
                        'created_by' => $scheme->created_by ?? null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'deposit_interest_rate_id')) {
                $table->dropForeign(['deposit_interest_rate_id']);
                $table->dropColumn('deposit_interest_rate_id');
            }
        });

        Schema::table('deposit_scheme_interest_rates', function (Blueprint $table) {
            if (Schema::hasColumn('deposit_scheme_interest_rates', 'effective_from')) {
                $table->dropColumn('effective_from');
            }
            if (Schema::hasColumn('deposit_scheme_interest_rates', 'effective_to')) {
                $table->dropColumn('effective_to');
            }
            if (Schema::hasColumn('deposit_scheme_interest_rates', 'calculation_method')) {
                $table->dropColumn('calculation_method');
            }
            if (Schema::hasColumn('deposit_scheme_interest_rates', 'updated_by')) {
                $table->dropForeign(['updated_by']);
                $table->dropColumn('updated_by');
            }
        });
    }
};
