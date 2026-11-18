<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $collectionPermIds = DB::table('permissions')
            ->where('group', 'collections')
            ->orWhereIn('slug', ['view-collections', 'create-collections', 'verify-collections', 'reverse-collections'])
            ->pluck('id');

        if ($collectionPermIds->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $collectionPermIds)->delete();
            DB::table('permissions')->whereIn('id', $collectionPermIds)->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Collections module has been removed from this project.
    }
};
