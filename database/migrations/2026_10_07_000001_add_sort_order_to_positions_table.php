<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 — Add sort_order to positions table.
 * The positions table already exists in the DB, so we only add the missing column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (! Schema::hasColumn('positions', 'sort_order')) {
                $table->unsignedInteger('sort_order')->default(0)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('positions', function (Blueprint $table) {
            if (Schema::hasColumn('positions', 'sort_order')) {
                $table->dropColumn('sort_order');
            }
        });
    }
};
