<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('deposit_schemes') || ! Schema::hasColumn('deposit_schemes', 'ksp_configuration')) {
            return;
        }

        $fdTypeId = Schema::hasTable('scheme_types')
            ? DB::table('scheme_types')->where('code', 'FD')->value('id')
            : null;

        $now = now();

        for ($yr = 1; $yr <= 10; $yr++) {
            $code = "KSP{$yr}Y";
            $endYear = 10 + $yr;

            $returnDesc = match ($yr) {
                1 => 'receive ₹45,000 return in the 11th Year',
                2 => 'receive ₹45,000/yr return in the 11th and 12th Year',
                default => "receive ₹45,000/yr return from 11th to {$endYear}th Year",
            };

            $kspConfig = [
                'annual_deposit_amount' => 15000.00,
                'deposit_period' => $yr,
                'insurance_coverage' => 300000.00,
                'return_start_year' => 11,
                'return_end_year' => $endYear,
                'annual_return_amount' => 45000.00,
                'return_frequency' => 'Yearly',
                'premature_withdrawal' => 'Not Allowed',
            ];

            $existing = DB::table('deposit_schemes')->where('code', $code)->first();

            if ($existing) {
                DB::table('deposit_schemes')
                    ->where('id', $existing->id)
                    ->update([
                        'ksp_configuration' => json_encode($kspConfig),
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('deposit_schemes')->insert([
                    'scheme_type_id' => $fdTypeId,
                    'code' => $code,
                    'name' => "Kissan Samrudhi Plan (KSP) {$yr} Year",
                    'scheme_type' => 'FD',
                    'description' => "Kissan Samrudhi Plan {$yr} Year: Deposit ₹15,000/yr for {$yr} ".($yr === 1 ? 'Year' : 'Years').", {$returnDesc} with ₹3,00,000 insurance coverage.",
                    'deposit_frequency' => 'one_time',
                    'min_amount' => 15000.00,
                    'max_amount' => null,
                    'tenure' => $yr,
                    'tenure_unit' => 'YEARS',
                    'lock_in_months' => $yr * 12,
                    'allow_premature_withdrawal' => 0,
                    'premature_penalty_percent' => 0.00,
                    'allow_loan_against_deposit' => 0,
                    'max_loan_percentage' => 0.00,
                    'interest_rate' => 0.00,
                    'is_double' => 0,
                    'total_commission_percentage' => 0.00,
                    'promotion_target_percentage' => null,
                    'ksp_configuration' => json_encode($kspConfig),
                    'interest_payout_frequency' => 'ANNUALLY',
                    'senior_citizen_extra_rate' => 0.00,
                    'collection_frequency' => 'ONE_TIME',
                    'grace_period_days' => 7,
                    'status' => 'ACTIVE',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('deposit_schemes')) {
            return;
        }

        $codes = [];
        for ($yr = 1; $yr <= 10; $yr++) {
            $codes[] = "KSP{$yr}Y";
        }

        DB::table('deposit_schemes')
            ->whereIn('code', $codes)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('deposits')
                    ->whereColumn('deposits.deposit_scheme_id', 'deposit_schemes.id');
            })
            ->delete();
    }
};
