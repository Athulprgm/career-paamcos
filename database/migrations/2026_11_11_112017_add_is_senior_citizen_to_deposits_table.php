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
            if (! Schema::hasColumn('deposits', 'is_senior_citizen')) {
                $table->boolean('is_senior_citizen')->default(false)->after('is_double');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'is_senior_citizen')) {
                $table->dropColumn('is_senior_citizen');
            }
        });
    }
};
