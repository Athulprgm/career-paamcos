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
        Schema::table('agents', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable()->change();
        });

        if (Schema::hasTable('promotion_requests') && Schema::hasColumn('promotion_requests', 'branch_id')) {
            Schema::table('promotion_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->unsignedBigInteger('branch_id')->nullable(false)->change();
        });

        if (Schema::hasTable('promotion_requests') && Schema::hasColumn('promotion_requests', 'branch_id')) {
            Schema::table('promotion_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable(false)->change();
            });
        }
    }
};
