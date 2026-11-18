<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('deposits', 'member_code')) {
            DB::statement('ALTER TABLE deposits MODIFY member_code VARCHAR(30) NULL');
        }
        if (Schema::hasColumn('deposits', 'created_by')) {
            DB::statement('ALTER TABLE deposits MODIFY created_by BIGINT UNSIGNED NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('deposits', 'member_code')) {
            DB::statement('ALTER TABLE deposits MODIFY member_code VARCHAR(30) NOT NULL');
        }
    }
};
