<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds a JSON column that stores multiple qualifying-agent tiers:
     * e.g. [{"count": 3, "rank_id": 5}, {"count": 2, "rank_id": null}]
     * null rank_id = "same as current designation (default)"
     */
    public function up(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->json('qualifying_agent_tiers')->nullable()->after('qualifying_rank_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->dropColumn('qualifying_agent_tiers');
        });
    }
};
