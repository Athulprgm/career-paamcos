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
            if (! Schema::hasColumn('deposit_schemes', 'ksp_configuration')) {
                $table->json('ksp_configuration')->nullable()->after('promotion_target_percentage');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposit_schemes', function (Blueprint $table) {
            if (Schema::hasColumn('deposit_schemes', 'ksp_configuration')) {
                $table->dropColumn('ksp_configuration');
            }
        });
    }
};
