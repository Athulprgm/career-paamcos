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
            if (! Schema::hasColumn('deposits', 'lead_id')) {
                $table->foreignId('lead_id')->nullable()->after('customer_id')->constrained('leads')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table) {
            if (Schema::hasColumn('deposits', 'lead_id')) {
                $table->dropForeign(['lead_id']);
                $table->dropColumn('lead_id');
            }
        });
    }
};
