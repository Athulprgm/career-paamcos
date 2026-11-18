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
        if (! Schema::hasColumn('agents', 'profile_image')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->string('profile_image', 2048)->nullable()->after('address');
            });
        }

        if (! Schema::hasColumn('users', 'profile_image')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('profile_image', 2048)->nullable()->after('branch_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('agents', 'profile_image')) {
            Schema::table('agents', function (Blueprint $table) {
                $table->dropColumn('profile_image');
            });
        }

        if (Schema::hasColumn('users', 'profile_image')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('profile_image');
            });
        }
    }
};
