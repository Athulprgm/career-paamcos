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
        Schema::table('deposit_schemes', function (Blueprint $table) {
            $table->decimal('promotion_target_percentage', 8, 2)->nullable()->after('total_commission_percentage');
        });

        // Seed default tenure target percentages for existing schemes
        $schemes = DB::table('deposit_schemes')->get();
        foreach ($schemes as $scheme) {
            $tenure = (int) ($scheme->tenure ?? 1);
            $unit = strtoupper(trim((string) ($scheme->tenure_unit ?? 'MONTHS')));
            $years = match ($unit) {
                'DAYS', 'DAY' => (int) floor($tenure / 365),
                'MONTHS', 'MONTH' => (int) floor($tenure / 12),
                'WEEKS', 'WEEK' => (int) floor(($tenure * 7) / 365),
                default => max(0, $tenure),
            };

            $type = strtoupper((string) ($scheme->scheme_type ?? 'FD'));
            $pct = match ($type) {
                'DD', 'DAILY', 'DAILY DEPOSIT' => match ($years) {
                    1 => 20.00,
                    2 => 40.00,
                    3 => 50.00,
                    default => null,
                },
                'RD', 'RECURRING', 'RECURRING DEPOSIT' => match ($years) {
                    1 => 20.00,
                    2 => 30.00,
                    3 => 40.00,
                    5 => 80.00,
                    6 => 80.00,
                    7 => 90.00,
                    default => null,
                },
                default => match ($years) {
                    1 => 20.00,
                    2 => 30.00,
                    3 => 40.00,
                    5 => 80.00,
                    6 => 80.00,
                    default => null,
                },
            };

            if ($pct !== null) {
                DB::table('deposit_schemes')
                    ->where('id', $scheme->id)
                    ->update(['promotion_target_percentage' => $pct]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_schemes', function (Blueprint $table) {
            $table->dropColumn('promotion_target_percentage');
        });
    }
};
