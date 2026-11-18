<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Standard system permissions organized by module category.
     *
     * @var array<string, array<int, array{slug: string, name: string, description: string}>>
     */
    protected array $permissions = [
        'deposits' => [
            [
                'slug' => 'view-deposits',
                'name' => 'View Deposit Accounts',
                'description' => 'View and search all customer deposit accounts across branches.',
            ],
            [
                'slug' => 'create-deposits',
                'name' => 'Create Deposit Accounts',
                'description' => 'Open and register new customer fixed deposit and recurring deposit accounts.',
            ],
            [
                'slug' => 'manage-deposits',
                'name' => 'Manage Deposit Accounts',
                'description' => 'Update, edit, and modify existing customer deposit account details.',
            ],
            [
                'slug' => 'verify-deposits',
                'name' => 'Verify Deposit Accounts',
                'description' => 'Review, verify, and approve customer deposit submissions.',
            ],
            [
                'slug' => 'close-deposits',
                'name' => 'Close / Mature Deposits',
                'description' => 'Process premature deposit account closures and maturity payouts.',
            ],
        ],
        'commissions' => [
            [
                'slug' => 'view-commissions',
                'name' => 'View Commissions',
                'description' => 'View commission ledger, agent payout histories, and generated earnings.',
            ],
            [
                'slug' => 'approve-commissions',
                'name' => 'Approve Commissions',
                'description' => 'Review, verify, and approve pending commission calculations for agents.',
            ],
            [
                'slug' => 'create-settlements',
                'name' => 'Disburse / Settle Commissions',
                'description' => 'Process commission disbursements, create settlement vouchers, and mark as paid.',
            ],
            [
                'slug' => 'manage-commission-rules',
                'name' => 'Manage Commission Rules',
                'description' => 'Configure and customize scheme commission slabs, rank percentages, and rules.',
            ],
        ],
        'customers' => [
            [
                'slug' => 'view-customers',
                'name' => 'View Customers',
                'description' => 'View customer directory, profiles, and associated deposit portfolios.',
            ],
            [
                'slug' => 'create-customers',
                'name' => 'Create Customers',
                'description' => 'Register and onboard new customers and account holders.',
            ],
            [
                'slug' => 'edit-customers',
                'name' => 'Edit Customers',
                'description' => 'Update customer profile information, contact numbers, and addresses.',
            ],
            [
                'slug' => 'verify-kyc',
                'name' => 'Verify Customer KYC',
                'description' => 'Review and verify customer identity, PAN, Aadhaar, and proof of address documents.',
            ],
        ],
        'staff' => [
            [
                'slug' => 'view-employees',
                'name' => 'View Staff Directory',
                'description' => 'View internal staff member profiles and branch organizational roster.',
            ],
            [
                'slug' => 'manage-staff',
                'name' => 'Manage Staff Members',
                'description' => 'Create, edit, toggle status, and manage branch staff accounts.',
            ],
            [
                'slug' => 'manage-positions',
                'name' => 'Manage Staff Positions',
                'description' => 'Configure internal staff designations, positions, and hierarchy levels.',
            ],
        ],
        'agents' => [
            [
                'slug' => 'view-agents',
                'name' => 'View Agents Directory',
                'description' => 'View field agents, member codes, downline structures, and profiles.',
            ],
            [
                'slug' => 'manage-agents',
                'name' => 'Manage Agents',
                'description' => 'Create, verify, approve, edit, and toggle status of field agents.',
            ],
            [
                'slug' => 'view-ranks',
                'name' => 'View Agent Ranks',
                'description' => 'View agent career levels, ranks, targets, and criteria definitions.',
            ],
            [
                'slug' => 'manage-ranks',
                'name' => 'Manage Agent Ranks',
                'description' => 'Configure agent career levels, qualification criteria, and promotion rules.',
            ],
            [
                'slug' => 'approve-promotions',
                'name' => 'Approve Agent Promotions',
                'description' => 'Review and approve agent career elevation requests and rank upgrades.',
            ],
        ],
        'leads' => [
            [
                'slug' => 'view-leads',
                'name' => 'View Leads',
                'description' => 'View customer leads and business opportunities submitted by agents.',
            ],
            [
                'slug' => 'manage-leads',
                'name' => 'Manage Leads',
                'description' => 'Update lead status, follow-up progress, notes, and convert leads to depositors.',
            ],
        ],
        'schemes' => [
            [
                'slug' => 'view-schemes',
                'name' => 'View Deposit Schemes',
                'description' => 'View available deposit schemes, tenures, interest slabs, and terms.',
            ],
            [
                'slug' => 'manage-schemes',
                'name' => 'Manage Deposit Schemes',
                'description' => 'Create, configure, and modify fixed deposit and recurring deposit schemes.',
            ],
        ],
        'reports' => [
            [
                'slug' => 'view-reports',
                'name' => 'View Reports',
                'description' => 'Access operational, financial, deposit, and commission reports and analytics.',
            ],
            [
                'slug' => 'export-reports',
                'name' => 'Export Reports',
                'description' => 'Download and export reports to Excel, CSV, or PDF formats.',
            ],
        ],
        'branches' => [
            [
                'slug' => 'view-branches',
                'name' => 'View Branches',
                'description' => 'View company branches, office locations, and contact details.',
            ],
            [
                'slug' => 'manage-branches',
                'name' => 'Manage Branches',
                'description' => 'Create, configure, update, and manage branch offices.',
            ],
        ],
        'settings' => [
            [
                'slug' => 'view-settings',
                'name' => 'View System Settings',
                'description' => 'View global system settings and organization preferences.',
            ],
            [
                'slug' => 'manage-settings',
                'name' => 'Manage System Settings',
                'description' => 'Configure and update global system settings.',
            ],
        ],
        'audit' => [
            [
                'slug' => 'view-audit-logs',
                'name' => 'View Audit Logs',
                'description' => 'Inspect comprehensive system audit trail, security events, and user activities.',
            ],
        ],
    ];

    /**
     * Default permissions assigned to standard system roles.
     *
     * @var array<string, array<int, string>>
     */
    protected array $rolePermissions = [
        'super_admin' => [
            '*',
        ],
        'admin' => [
            '*',
        ],
        'finance' => [
            'view-commissions',
            'approve-commissions',
            'create-settlements',
            'view-deposits',
            'verify-deposits',
            'view-schemes',
            'view-customers',
            'view-reports',
            'export-reports',
        ],
        'staff' => [
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
            'view-leads',
            'manage-leads',
            'verify-kyc',
        ],
        'employee' => [
            'view-customers',
            'view-schemes',
            'view-deposits',
            'view-leads',
            'view-reports',
        ],
        'facilitator' => [
            'view-customers',
            'create-customers',
            'view-leads',
            'manage-leads',
            'view-schemes',
        ],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $allPermissions = [];

        foreach ($this->permissions as $group => $items) {
            foreach ($items as $item) {
                $permission = Permission::updateOrCreate(
                    ['slug' => $item['slug']],
                    [
                        'name' => $item['name'],
                        'group' => $group,
                        'description' => $item['description'],
                    ]
                );
                $allPermissions[$item['slug']] = $permission;
            }
        }

        // Map default permissions to standard roles
        foreach ($this->rolePermissions as $roleSlug => $slugs) {
            $role = Role::where('slug', $roleSlug)->first();
            if (! $role) {
                continue;
            }

            if (in_array('*', $slugs, true)) {
                $permIds = collect($allPermissions)->pluck('id')->toArray();
            } else {
                $permIds = collect($allPermissions)
                    ->whereIn('slug', $slugs)
                    ->pluck('id')
                    ->toArray();
            }

            $role->permissions()->syncWithoutDetaching($permIds);
        }
    }
}
