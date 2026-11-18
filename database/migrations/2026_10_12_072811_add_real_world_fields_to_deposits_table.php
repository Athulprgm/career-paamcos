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
        Schema::table('deposits', function (Blueprint $table) {
            if (! Schema::hasColumn('deposits', 'payment_mode')) {
                $table->string('payment_mode', 50)->default('CASH')->after('deposit_date');
            }
            if (! Schema::hasColumn('deposits', 'maturity_amount')) {
                $table->decimal('maturity_amount', 15, 2)->nullable()->after('maturity_date');
            }
            if (! Schema::hasColumn('deposits', 'nominee_name')) {
                $table->string('nominee_name', 255)->nullable()->after('notes');
            }
            if (! Schema::hasColumn('deposits', 'nominee_relation')) {
                $table->string('nominee_relation', 50)->nullable()->after('nominee_name');
            }
            if (! Schema::hasColumn('deposits', 'nominee_phone')) {
                $table->string('nominee_phone', 20)->nullable()->after('nominee_relation');
            }
            if (! Schema::hasColumn('deposits', 'nominee_age')) {
                $table->unsignedTinyInteger('nominee_age')->nullable()->after('nominee_phone');
            }
            if (! Schema::hasColumn('deposits', 'auto_renewal')) {
                $table->string('auto_renewal', 50)->default('NONE')->after('nominee_age');
            }
            if (! Schema::hasColumn('deposits', 'payout_frequency')) {
                $table->string('payout_frequency', 50)->default('AT_MATURITY')->after('auto_renewal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            $columnsToDrop = [];
            foreach (['payout_frequency', 'auto_renewal', 'nominee_age', 'nominee_phone', 'nominee_relation', 'nominee_name', 'maturity_amount', 'payment_mode'] as $col) {
                if (Schema::hasColumn('deposits', $col)) {
                    $columnsToDrop[] = $col;
                }
            }
            if (! empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
