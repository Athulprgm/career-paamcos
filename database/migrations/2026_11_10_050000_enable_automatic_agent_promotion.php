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
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(false)->change();
        });

        // Set all existing promotion rules to auto-promote without approval
        DB::table('promotion_rules')->update(['requires_approval' => false]);

        // Update career settings to auto-promote without approval
        DB::table('settings')->where('key', 'career.auto_evaluate_promotions')->update(['value' => '1']);
        DB::table('settings')->where('key', 'career.require_super_admin_approval')->update(['value' => '0']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_rules', function (Blueprint $table) {
            $table->boolean('requires_approval')->default(true)->change();
        });

        DB::table('settings')->where('key', 'career.auto_evaluate_promotions')->update(['value' => '0']);
        DB::table('settings')->where('key', 'career.require_super_admin_approval')->update(['value' => '1']);
    }
};
