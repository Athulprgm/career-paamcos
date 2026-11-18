<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $staffRole = DB::table('roles')->where('slug', 'staff')->first();
        if ($staffRole) {
            $permissionSlugs = [
                'view-ranks',
                'view-employees',
                'view-customers',
                'create-customers',
                'edit-customers',
                'view-schemes',
                'view-deposits',
                'create-deposits',
                'view-commissions',
                'view-reports',
                'export-reports',
            ];

            $permissions = DB::table('permissions')
                ->whereIn('slug', $permissionSlugs)
                ->get();

            foreach ($permissions as $perm) {
                $exists = DB::table('permission_role')
                    ->where('role_id', $staffRole->id)
                    ->where('permission_id', $perm->id)
                    ->exists();

                if (! $exists) {
                    DB::table('permission_role')->insert([
                        'role_id' => $staffRole->id,
                        'permission_id' => $perm->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        $staffRole = DB::table('roles')->where('slug', 'staff')->first();
        if ($staffRole) {
            DB::table('permission_role')->where('role_id', $staffRole->id)->delete();
        }
    }
};
