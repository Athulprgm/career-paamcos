<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class ClearDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:clear {--force : Force the operation to run without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear all dummy and transactional data while preserving Super Admin credentials, system roles, settings, and rank structure.';

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
        'customer' => [
            'name' => 'Customer',
            'description' => 'Customer / depositor account.',
        ],
    ];

    /**
     * Tables to truncate completely.
     *
     * @var array<int, string>
     */
    protected array $tablesToTruncate = [
        'audit_logs',
        'collection_payments',
        'deposit_collections',
        'deposits',
        'customer_kyc',
        'customers',
        'leads',
        'commission_ledgers',
        'career_histories',
        'agent_promotions',
        'promotion_requests',
        'promotion_rules',
        'agent_hierarchies',
        'agents',
        'staff',
        'branches',
        'deposit_scheme_interest_rates',
        'deposit_scheme_commission_histories',
        'deposit_scheme_rank_commissions',
        'deposit_scheme_terms',
        'deposit_schemes',
        'commission_rules',
        'scheme_types',
        'positions',
        'sessions',
        'failed_jobs',
        'jobs',
        'job_batches',
        'password_reset_tokens',
        'cache',
        'cache_locks',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force')) {
            $confirmed = $this->confirm(
                'Are you sure you want to clear all dummy/operational data while keeping Super Admin credentials?',
                false
            );

            if (! $confirmed) {
                $this->warn('Operation cancelled.');

                return self::FAILURE;
            }
        }

        $this->info('Starting database clearing while preserving Super Admin credentials...');

        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        try {
            // 1. Truncate dummy/operational and transaction tables
            foreach ($this->tablesToTruncate as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                    $this->line("  ✓ Cleared table: <comment>{$table}</comment>");
                }
            }

            // 2. Ensure system roles exist
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
            $this->line('  ✓ Verified 8 standard system roles.');

            // 3. Identify and preserve all Super Admin user accounts
            $superAdminRoleId = $roleModels['super_admin']->id ?? null;
            $superAdminUserIds = [];

            if ($superAdminRoleId) {
                $roleUserIds = DB::table('role_user')
                    ->where('role_id', $superAdminRoleId)
                    ->pluck('user_id')
                    ->toArray();
                $superAdminUserIds = array_merge($superAdminUserIds, $roleUserIds);
            }

            // Check if superadmin@multicred.com exists
            $defaultSuperAdmin = User::where('email', 'superadmin@multicred.com')->first();
            if ($defaultSuperAdmin && ! in_array($defaultSuperAdmin->id, $superAdminUserIds, true)) {
                $superAdminUserIds[] = $defaultSuperAdmin->id;
            }

            // If no Super Admin user exists at all, create the default one
            if (empty($superAdminUserIds)) {
                $createdSuperAdmin = User::create([
                    'name' => 'Super Administrator',
                    'email' => 'superadmin@multicred.com',
                    'password' => Hash::make('password'),
                    'branch_id' => null,
                    'email_verified_at' => now(),
                ]);
                $superAdminUserIds[] = $createdSuperAdmin->id;
                $this->line('  ✓ Created fresh Super Admin user (superadmin@multicred.com).');
            }

            // Set Super Admin password to 'password' and clear any assigned branch_id
            User::whereIn('id', $superAdminUserIds)->update([
                'password' => Hash::make('password'),
                'branch_id' => null,
            ]);

            // Ensure all preserved Super Admin users have the super_admin role linked
            if ($superAdminRoleId) {
                foreach ($superAdminUserIds as $saId) {
                    $hasRole = DB::table('role_user')
                        ->where('user_id', $saId)
                        ->where('role_id', $superAdminRoleId)
                        ->exists();

                    if (! $hasRole) {
                        DB::table('role_user')->insert([
                            'user_id' => $saId,
                            'role_id' => $superAdminRoleId,
                        ]);
                    }
                }
            }

            // 4. Delete all non-superadmin users and their role associations
            if (Schema::hasTable('users')) {
                $deletedUsers = DB::table('users')->whereNotIn('id', $superAdminUserIds)->delete();
                $this->line("  ✓ Removed {$deletedUsers} dummy/non-superadmin user(s).");
            }

            if (Schema::hasTable('role_user')) {
                DB::table('role_user')->whereNotIn('user_id', $superAdminUserIds)->delete();
                $this->line('  ✓ Cleaned non-superadmin role associations.');
            }

            // 5. Clean dummy uploads in storage
            $this->cleanStorageDirectory(storage_path('app/public/agents'));
            $this->cleanStorageDirectory(storage_path('app/private/kyc_documents'));
            $this->line('  ✓ Cleaned dummy storage files (agents, kyc_documents).');

        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        }

        $this->newLine();
        $this->info('====================================================');
        $this->info('Database cleared successfully! Fresh database ready.');
        $this->info('====================================================');
        $this->line('Preserved Super Admin Accounts:');

        $preservedUsers = User::whereIn('id', $superAdminUserIds)->get();
        foreach ($preservedUsers as $pu) {
            $pu->ensureSuperAdminAgentProfile();
            $this->line("  • <info>Email:</info> {$pu->email} | <info>Name:</info> {$pu->name} (Credentials & Deputy Director Profile intact)");
        }
        $this->info('====================================================');

        return self::SUCCESS;
    }

    /**
     * Clean directory files except .gitignore.
     */
    protected function cleanStorageDirectory(string $path): void
    {
        if (! File::isDirectory($path)) {
            return;
        }

        $files = File::files($path);
        foreach ($files as $file) {
            if ($file->getFilename() !== '.gitignore') {
                File::delete($file->getPathname());
            }
        }
    }
}
