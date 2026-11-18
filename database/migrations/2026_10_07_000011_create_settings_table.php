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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique()->index();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, boolean, integer, float, json, file
            $table->string('group')->index();          // society, branch, deposit, collection, commission, career, numbering, notification, system
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Seed initial default system configuration
        $now = now();
        $defaults = [
            // ─── 1. Society Profile ───────────────────────────────────────────
            [
                'key' => 'society.name',
                'value' => 'Pristine Agro & Allied Multi State Co-operative Society Ltd.',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Official registered name of the co-operative society',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.short_name',
                'value' => 'PAAMCO',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Short abbreviation or brand name of the society',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.registration_number',
                'value' => 'MSCS/CR/1725/2026',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Multi-state co-operative societies registration number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.address',
                'value' => 'MultiCred Bhavan, 4th Floor, Banking District, Metro Complex, New Delhi - 110001',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Official registered office address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.phone',
                'value' => '+91 11 2345 6789',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Primary contact telephone number',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.email',
                'value' => 'contact@multicred.coop',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Primary official email address',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.website',
                'value' => 'https://www.multicred.coop',
                'type' => 'string',
                'group' => 'society',
                'description' => 'Official web portal URL',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'society.logo',
                'value' => null,
                'type' => 'file',
                'group' => 'society',
                'description' => 'Society brand logo image path',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 2. Branch Settings ───────────────────────────────────────────
            [
                'key' => 'branch.default_code_prefix',
                'value' => 'BR',
                'type' => 'string',
                'group' => 'branch',
                'description' => 'Default prefix for new branch code generation',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'branch.require_admin_assignment',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'branch',
                'description' => 'Require branch admin assignment when creating a branch',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'branch.allow_inter_branch_view',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'branch',
                'description' => 'Allow branch admins to view other branches in read-only mode',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'branch.max_agents_per_branch',
                'value' => '250',
                'type' => 'integer',
                'group' => 'branch',
                'description' => 'Maximum active agents allowed per branch',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 3. Deposit Settings ──────────────────────────────────────────
            [
                'key' => 'deposit.require_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'deposit',
                'description' => 'Require administrative approval before activating deposit accounts',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'deposit.allow_premature_closure',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'deposit',
                'description' => 'Allow customers to request premature deposit closure with penalty rules',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'deposit.minimum_tenure_months',
                'value' => '1',
                'type' => 'integer',
                'group' => 'deposit',
                'description' => 'Minimum allowable tenure in months for term deposits',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'deposit.maximum_deposit_amount',
                'value' => '10000000',
                'type' => 'float',
                'group' => 'deposit',
                'description' => 'Maximum allowed deposit balance per account (INR)',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'deposit.default_grace_period_days',
                'value' => '7',
                'type' => 'integer',
                'group' => 'deposit',
                'description' => 'Grace period days allowed after maturity before auto-renewal or penalty',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 4. Collection Settings ───────────────────────────────────────
            [
                'key' => 'collection.allow_partial_payment',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'collection',
                'description' => 'Allow collectors to record partial installment payments',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'collection.grace_period_days',
                'value' => '3',
                'type' => 'integer',
                'group' => 'collection',
                'description' => 'Grace days before an unpaid collection installment marks overdue',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'collection.auto_generate_commission',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'collection',
                'description' => 'Automatically trigger commission calculation upon collection receipt',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'collection.default_payment_method',
                'value' => 'CASH',
                'type' => 'string',
                'group' => 'collection',
                'description' => 'Default selected payment method for field collection entries',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 5. Commission Settings ───────────────────────────────────────
            [
                'key' => 'commission.require_admin_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'commission',
                'description' => 'Require admin approval before marking commissions payable',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'commission.auto_approve',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'commission',
                'description' => 'Automatically approve calculated commissions without manual review',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'commission.minimum_payout_amount',
                'value' => '100.00',
                'type' => 'float',
                'group' => 'commission',
                'description' => 'Minimum accumulated commission required for disbursement payout',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'commission.payout_frequency',
                'value' => 'monthly',
                'type' => 'string',
                'group' => 'commission',
                'description' => 'Standard commission disbursement cycle (weekly, bi-weekly, monthly)',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 6. Career Settings ───────────────────────────────────────────
            [
                'key' => 'career.auto_evaluate_promotions',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'career',
                'description' => 'Automatically scan and submit promotion requests for eligible agents',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'career.evaluation_cycle_days',
                'value' => '30',
                'type' => 'integer',
                'group' => 'career',
                'description' => 'Performance evaluation review window in days',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'career.require_super_admin_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'career',
                'description' => 'Require Super Admin or Branch Admin approval for promotion requests',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 7. Numbering Settings ────────────────────────────────────────
            [
                'key' => 'numbering.customer_prefix',
                'value' => 'CUST',
                'type' => 'string',
                'group' => 'numbering',
                'description' => 'Customer identification number prefix',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.customer_digits',
                'value' => '6',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Numeric padding length for customer numbers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.customer_next',
                'value' => '1',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Next sequential customer number counter',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'key' => 'numbering.deposit_prefix',
                'value' => 'DEP',
                'type' => 'string',
                'group' => 'numbering',
                'description' => 'Deposit account number prefix',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.deposit_digits',
                'value' => '6',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Numeric padding length for deposit account numbers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.deposit_next',
                'value' => '1',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Next sequential deposit account number counter',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'key' => 'numbering.collection_prefix',
                'value' => 'COL',
                'type' => 'string',
                'group' => 'numbering',
                'description' => 'Deposit collection installment receipt prefix',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.collection_digits',
                'value' => '6',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Numeric padding length for collection receipt numbers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.collection_next',
                'value' => '1',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Next sequential collection receipt number counter',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'key' => 'numbering.commission_prefix',
                'value' => 'COM',
                'type' => 'string',
                'group' => 'numbering',
                'description' => 'Commission transaction ledger voucher prefix',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.commission_digits',
                'value' => '6',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Numeric padding length for commission ledger numbers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.commission_next',
                'value' => '1',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Next sequential commission ledger voucher counter',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            [
                'key' => 'numbering.promotion_prefix',
                'value' => 'PR',
                'type' => 'string',
                'group' => 'numbering',
                'description' => 'Career promotion request reference prefix',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.promotion_digits',
                'value' => '6',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Numeric padding length for promotion request reference numbers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'numbering.promotion_next',
                'value' => '1',
                'type' => 'integer',
                'group' => 'numbering',
                'description' => 'Next sequential promotion request number counter',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 8. Notification Settings ─────────────────────────────────────
            [
                'key' => 'notification.email_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Enable outbound email notifications for society transactions',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notification.sms_enabled',
                'value' => '0',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Enable outbound SMS notifications for payment confirmations',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notification.notify_deposit_approval',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send alert notification when a deposit account is approved/activated',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notification.notify_collection_receipt',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send alert notification when an installment payment is collected',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notification.notify_commission_approved',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send alert notification to agent when commission is approved/paid',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'notification.notify_promotion_requested',
                'value' => '1',
                'type' => 'boolean',
                'group' => 'notification',
                'description' => 'Send alert notification when a new agent promotion request is logged',
                'created_at' => $now,
                'updated_at' => $now,
            ],

            // ─── 9. System Preferences ────────────────────────────────────────
            [
                'key' => 'system.currency_symbol',
                'value' => '₹',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Primary currency symbol displayed across financial ledgers',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'system.currency_code',
                'value' => 'INR',
                'type' => 'string',
                'group' => 'system',
                'description' => 'ISO standard 3-letter currency code',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'system.date_format',
                'value' => 'd/m/Y',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Default system-wide date display format',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'system.timezone',
                'value' => 'Asia/Kolkata',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Application regional timezone',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'system.pagination_limit',
                'value' => '15',
                'type' => 'integer',
                'group' => 'system',
                'description' => 'Standard records per page in data tables',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'key' => 'system.dashboard_chart_period',
                'value' => '30_days',
                'type' => 'string',
                'group' => 'system',
                'description' => 'Default financial chart reporting window on admin dashboards',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        DB::table('settings')->insert($defaults);

        // Add permissions for settings management
        $settingPermissions = [
            [
                'name' => 'View System Settings',
                'slug' => 'view-settings',
                'group' => 'settings',
                'description' => 'View global system settings and organization preferences.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Manage System Settings',
                'slug' => 'manage-settings',
                'group' => 'settings',
                'description' => 'Configure and update global system settings.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($settingPermissions as $perm) {
            $existing = DB::table('permissions')->where('slug', $perm['slug'])->first();
            if (! $existing) {
                $id = DB::table('permissions')->insertGetId($perm);
            } else {
                $id = $existing->id;
            }

            // Assign to admin role
            $adminRole = DB::table('roles')->where('slug', 'admin')->first();
            if ($adminRole) {
                $hasRolePerm = DB::table('permission_role')
                    ->where('role_id', $adminRole->id)
                    ->where('permission_id', $id)
                    ->exists();
                if (! $hasRolePerm) {
                    DB::table('permission_role')->insert([
                        'role_id' => $adminRole->id,
                        'permission_id' => $id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');

        $perms = DB::table('permissions')->whereIn('slug', ['view-settings', 'manage-settings'])->pluck('id');
        if ($perms->isNotEmpty()) {
            DB::table('permission_role')->whereIn('permission_id', $perms)->delete();
            DB::table('permissions')->whereIn('id', $perms)->delete();
        }
    }
};
