<?php

use App\Models\PromotionRule;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Default tenure-based promotion target percentages requested:
     * FD & RD: 1Y: 20%, 2Y: 30%, 3Y: 40%, 5Y: 80%, 6Y: 80%, 7Y: 90%
     * DD: 1Y: 20%, 2Y: 40%, 3Y: 50%
     */
    public static function getDefaultTenureTargetPercentages(): array
    {
        return [
            'fd_rd' => [
                '1' => 20.0,
                '2' => 30.0,
                '3' => 40.0,
                '5' => 80.0,
                '6' => 80.0,
                '7' => 90.0,
            ],
            'dd' => [
                '1' => 20.0,
                '2' => 40.0,
                '3' => 50.0,
            ],
        ];
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('promotion_rules', 'apply_tenure_targets')) {
                $table->boolean('apply_tenure_targets')->default(false)->after('fd_composition_percentage');
            }
            if (! Schema::hasColumn('promotion_rules', 'tenure_target_percentages')) {
                $table->longText('tenure_target_percentages')->nullable()->after('apply_tenure_targets');
            }
        });

        $defaultTargets = self::getDefaultTenureTargetPercentages();
        $defaultJson = json_encode($defaultTargets);

        // Seed global default settings in settings table if settings table exists
        if (Schema::hasTable('settings')) {
            $settings = [
                [
                    'key' => 'career.rd_composition_percentage',
                    'value' => '10.00',
                    'type' => 'float',
                    'group' => 'career',
                    'description' => 'Compulsory minimum Recurring Deposit (RD) contribution percentage towards promotion targets',
                ],
                [
                    'key' => 'career.fd_composition_percentage',
                    'value' => '90.00',
                    'type' => 'float',
                    'group' => 'career',
                    'description' => 'Maximum Fixed Deposit (FD) / lump sum contribution percentage eligible towards promotion targets',
                ],
                [
                    'key' => 'career.tenure_target_percentages',
                    'value' => $defaultJson,
                    'type' => 'json',
                    'group' => 'career',
                    'description' => 'Tenure-based target credit weightage percentages for FD, RD, and DD schemes',
                ],
            ];

            foreach ($settings as $setting) {
                DB::table('settings')->updateOrInsert(
                    ['key' => $setting['key']],
                    [
                        'value' => $setting['value'],
                        'type' => $setting['type'],
                        'group' => $setting['group'],
                        'description' => $setting['description'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }

        // Initialize existing promotion rules with the default tenure target percentages
        PromotionRule::query()->whereNull('tenure_target_percentages')->update([
            'tenure_target_percentages' => $defaultTargets,
            'rd_composition_percentage' => DB::raw('COALESCE(rd_composition_percentage, 10.00)'),
            'fd_composition_percentage' => DB::raw('COALESCE(fd_composition_percentage, 90.00)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            if (Schema::hasColumn('promotion_rules', 'tenure_target_percentages')) {
                $table->dropColumn('tenure_target_percentages');
            }
            if (Schema::hasColumn('promotion_rules', 'apply_tenure_targets')) {
                $table->dropColumn('apply_tenure_targets');
            }
        });

        if (Schema::hasTable('settings')) {
            DB::table('settings')->whereIn('key', [
                'career.rd_composition_percentage',
                'career.fd_composition_percentage',
                'career.tenure_target_percentages',
            ])->delete();
        }
    }
};
