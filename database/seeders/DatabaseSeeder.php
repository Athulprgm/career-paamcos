<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Standard system roles to maintain.
     *
     * @var array<string, array{name: string, description: string}>
     */
    protected array $standardRoles = [
        'super_admin' => [
            'name' => 'Super Admin',
            'description' => 'Full administrative access across all system operations, settings, and hierarchy management.',
        ],
        'admin' => [
            'name' => 'Admin',
            'description' => 'Branch and operational administrator managing employees, ranks, and structure.',
        ],
        'staff' => [
            'name' => 'Staff',
            'description' => 'Internal employee under Admin with permission-based branch access. Staff never appear in hierarchy and never receive agent rank.',
        ],
        'agent' => [
            'name' => 'Agent',
            'description' => 'Business and commission member with Member Code, rank, upline/downline hierarchy.',
        ],
        'employee' => [
            'name' => 'Employee',
            'description' => 'General organizational employee.',
        ],
        'facilitator' => [
            'name' => 'Facilitator',
            'description' => 'External business facilitator.',
        ],
        'finance' => [
            'name' => 'Finance',
            'description' => 'Finance and accounting management role.',
        ],
    ];

    /**
     * Seed the application's database with roles and Super Admin credentials.
     */
    public function run(): void
    {
        $roleModels = [];
        foreach ($this->standardRoles as $slug => $data) {
            $roleModels[$slug] = Role::firstOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'status' => 'active',
                ]
            );
        }

        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@multicred.com'],
            [
                'name' => 'Super Administrator',
                'password' => Hash::make('password'),
                'branch_id' => null,
                'email_verified_at' => now(),
            ]
        );

        if (isset($roleModels['super_admin'])) {
            $superAdmin->roles()->syncWithoutDetaching([$roleModels['super_admin']->id]);
        }

        // Remove all other users and clean up their associations
        if (DB::getDriverName() === 'mysql') {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        }

        try {
            DB::table('role_user')->where('user_id', '!=', $superAdmin->id)->delete();
            if (Schema::hasTable('staff')) {
                DB::table('staff')->where('user_id', '!=', $superAdmin->id)->update(['user_id' => null]);
            }
            if (Schema::hasTable('agents')) {
                DB::table('agents')->where('user_id', '!=', $superAdmin->id)->update(['user_id' => null]);
            }
            User::where('id', '!=', $superAdmin->id)->delete();
        } finally {
            if (DB::getDriverName() === 'mysql') {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            }
        }

        $this->call([
            PermissionSeeder::class,
            AgentRankSeeder::class,
        ]);

        $superAdmin->ensureSuperAdminAgentProfile();
    }
}
