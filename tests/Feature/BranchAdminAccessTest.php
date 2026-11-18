<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * BA-1 Branch Admin Access Tests
 *
 * Tests branch isolation, middleware, dashboard access,
 * cross-branch denial, and Super Admin regression.
 */
class BranchAdminAccessTest extends TestCase
{
    use DatabaseTransactions;

    // ── Helpers ──────────────────────────────────────────────────────────────

    protected function getDefaultRankId(): int
    {
        return AgentRank::value('id') ?? AgentRank::create([
            'name' => 'Agent Rank '.uniqid(),
            'code' => 'RK_'.uniqid(),
            'level' => (AgentRank::max('level') ?? 0) + 1,
            'sort_order' => 1,
            'status' => 'active',
        ])->id;
    }

    protected function findOrCreateRole(string $slug, string $name = ''): Role
    {
        return Role::firstOrCreate(
            ['slug' => $slug],
            ['name' => $name ?: ucwords(str_replace('_', ' ', $slug)), 'status' => 'active']
        );
    }

    protected function makeSuperAdmin(): User
    {
        $role = $this->findOrCreateRole('super_admin', 'Super Admin');
        $user = User::factory()->create(['branch_id' => null]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    protected function createBranch(array $attrs = []): Branch
    {
        $uniq = uniqid();

        return Branch::create(array_merge([
            'code' => 'BR_'.substr($uniq, -6),
            'name' => 'Branch '.$uniq,
            'address' => '123 Test Street',
            'phone' => '9876543210',
            'email' => 'branch_'.$uniq.'@example.com',
            'is_active' => true,
        ], $attrs));
    }

    protected function makeBranchAdmin(?Branch $branch = null): User
    {
        $role = $this->findOrCreateRole('admin', 'Branch Admin');
        $branch ??= $this->createBranch();
        $user = User::factory()->create(['branch_id' => $branch->id]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    protected function makeBranchAdminWithoutBranch(): User
    {
        $role = $this->findOrCreateRole('admin', 'Branch Admin');
        $user = User::factory()->create(['branch_id' => null]);
        $user->roles()->sync([$role->id]);

        return $user;
    }

    protected function createCustomer(array $attrs = []): Customer
    {
        $uniq = uniqid();
        $branchId = $attrs['branch_id'] ?? 1;
        $agent = Agent::where('branch_id', $branchId)->first() ?? Agent::create([
            'member_code' => 'AGT_'.strtoupper(substr($uniq, -6)),
            'agent_id' => 'AGT_'.strtoupper(substr($uniq, -6)),
            'name' => 'Agent '.$uniq,
            'email' => 'agent_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branchId,
            'rank_id' => $this->getDefaultRankId(),
            'status' => 'active',
            'joining_date' => now(),
        ]);

        return Customer::create(array_merge([
            'customer_number' => 'CUST_'.strtoupper(substr($uniq, -6)),
            'name' => 'Customer '.$uniq,
            'customer_type' => 'individual',
            'email' => 'cust_'.$uniq.'@example.com',
            'mobile' => '9876543210',
            'date_of_birth' => '1990-01-01',
            'address' => '456 Test Road',
            'pin_code' => '600001',
            'branch_id' => $branchId,
            'agent_id' => $attrs['agent_id'] ?? $agent->id,
            'status' => 'ACTIVE',
        ], $attrs));
    }

    protected function createDeposit(array $attrs = []): Deposit
    {
        $uniq = uniqid();
        $branchId = $attrs['branch_id'] ?? 1;
        $agent = Agent::where('branch_id', $branchId)->first() ?? Agent::create([
            'member_code' => 'AGT_'.strtoupper(substr($uniq, -6)),
            'agent_id' => 'AGT_'.strtoupper(substr($uniq, -6)),
            'name' => 'Agent '.$uniq,
            'email' => 'agent_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branchId,
            'rank_id' => $this->getDefaultRankId(),
            'status' => 'active',
            'joining_date' => now(),
        ]);

        return Deposit::create(array_merge([
            'deposit_number' => 'DEP_'.strtoupper(substr($uniq, -6)),
            'deposit_id' => 'DEP_'.strtoupper(substr($uniq, -6)),
            'customer_id' => $attrs['customer_id'] ?? null,
            'branch_id' => $branchId,
            'agent_id' => $attrs['agent_id'] ?? $agent->id,
            'deposit_type' => 'recurring',
            'amount' => 10000,
            'start_date' => now(),
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'collection_frequency' => 'MONTHLY',
            'status' => 'ACTIVE',
        ], $attrs));
    }

    // ── Tests: Login redirect ─────────────────────────────────────────────

    public function test_branch_admin_is_redirected_to_branch_dashboard(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get('/');

        $response->assertRedirect(route('branch-admin.dashboard'));
    }

    public function test_super_admin_is_redirected_to_superadmin_dashboard(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get('/');

        $response->assertRedirect(route('superadmin.dashboard'));
    }

    public function test_super_admin_can_log_in_via_login_form(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $superAdmin->update(['password' => bcrypt('password123')]);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => $superAdmin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('superadmin.dashboard'));
        $this->assertAuthenticatedAs($superAdmin);
    }

    public function test_super_admin_can_log_in_using_admin_username_shortcut(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        $superAdmin->update(['password' => bcrypt('password123')]);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => 'admin',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('superadmin.dashboard'));
        $this->assertTrue(auth()->check() && auth()->user()->isSuperAdmin());
    }

    public function test_branch_admin_post_login_redirects_to_branch_dashboard(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);
        $admin->update(['password' => bcrypt('password123')]);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => $admin->email,
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('branch-admin.dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    // ── Tests: Branch Admin Dashboard ─────────────────────────────────────

    public function test_branch_admin_can_access_branch_dashboard(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('branch-admin.dashboard');
    }

    public function test_branch_admin_without_branch_is_denied_dashboard(): void
    {
        $admin = $this->makeBranchAdminWithoutBranch();

        $response = $this->actingAs($admin)->get(route('branch-admin.dashboard'));

        $response->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_from_branch_dashboard(): void
    {
        $response = $this->get(route('branch-admin.dashboard'));

        $response->assertRedirect(route('superadmin.login'));
    }

    public function test_super_admin_cannot_access_branch_admin_dashboard(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('branch-admin.dashboard'));

        $response->assertForbidden();
    }

    // ── Tests: Super Admin Regression ─────────────────────────────────────

    public function test_super_admin_can_still_access_superadmin_dashboard(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('superadmin.dashboard'));

        $response->assertOk();
    }

    public function test_super_admin_can_still_access_admin_routes(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('admin.deposits.index'));

        $response->assertOk();
    }

    public function test_superadmin_branches_routes_still_work(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin)->get(route('superadmin.branches.index'));

        $response->assertOk();
    }

    // ── Tests: Customer Routes Removed ───────────────────────────────────

    public function test_customer_module_routes_are_completely_removed(): void
    {
        $branchA = $this->createBranch(['name' => 'Branch Alpha '.uniqid()]);
        $adminA = $this->makeBranchAdmin($branchA);
        $custA = $this->createCustomer(['branch_id' => $branchA->id, 'name' => 'Alpha Customer', 'status' => 'ACTIVE']);

        $responseIndex = $this->actingAs($adminA)->get('/admin/customers');
        $responseIndex->assertStatus(404);

        $responseShow = $this->actingAs($adminA)->get("/admin/customers/{$custA->id}");
        $responseShow->assertStatus(404);
    }

    // ── Tests: Branch Isolation — Deposits ───────────────────────────────

    public function test_branch_admin_cannot_view_another_branch_deposit_by_id(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $custB = $this->createCustomer(['branch_id' => $branchB->id]);
        $depositB = $this->createDeposit([
            'branch_id' => $branchB->id,
            'customer_id' => $custB->id,
        ]);

        $response = $this->actingAs($adminA)->get(route('admin.deposits.show', $depositB));

        $response->assertForbidden();
    }

    // ── Tests: Dashboard stats scoped to branch ───────────────────────────

    public function test_dashboard_statistics_are_scoped_to_assigned_branch(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $this->createCustomer(['branch_id' => $branchA->id, 'status' => 'ACTIVE']);
        $this->createCustomer(['branch_id' => $branchA->id, 'status' => 'ACTIVE']);
        $this->createCustomer(['branch_id' => $branchA->id, 'status' => 'ACTIVE']);

        $this->createCustomer(['branch_id' => $branchB->id, 'status' => 'ACTIVE']);
        $this->createCustomer(['branch_id' => $branchB->id, 'status' => 'ACTIVE']);

        $response = $this->actingAs($adminA)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['active_customers'] === 3;
        });
    }

    public function test_empty_branch_dashboard_shows_zeros_without_errors(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('stats', function ($stats) {
            return $stats['active_customers'] === 0
                && $stats['total_deposits'] === 0
                && $stats['pending_commission'] === 0;
        });
    }

    // ── Tests: Branch Admin Scheme Feature Restriction ────────────────────

    public function test_branch_admin_cannot_access_deposit_schemes_index(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('admin.deposit-schemes.index'));

        $response->assertForbidden();
    }

    public function test_branch_admin_cannot_access_deposit_schemes_create(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('admin.deposit-schemes.create'));

        $response->assertForbidden();
    }

    public function test_branch_admin_cannot_store_deposit_scheme(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->post(route('admin.deposit-schemes.store'), [
            'name' => 'Branch Scheme Unauthorized',
            'code' => 'UNAUTH_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.5,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response->assertForbidden();
    }

    public function test_branch_admin_cannot_manage_existing_deposit_scheme(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $scheme = DepositScheme::firstOrCreate(
            ['code' => 'TEST_SCHEME_BA'],
            [
                'name' => 'Test Scheme BA',
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 1000,
                'tenure' => 12,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 7.5,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]
        );

        // Edit
        $this->actingAs($admin)
            ->get(route('admin.deposit-schemes.edit', $scheme))
            ->assertForbidden();

        // Update
        $this->actingAs($admin)
            ->put(route('admin.deposit-schemes.update', $scheme), ['name' => 'Attempted Edit'])
            ->assertForbidden();

        // Toggle Status
        $this->actingAs($admin)
            ->post(route('admin.deposit-schemes.toggle-status', $scheme))
            ->assertForbidden();

        // Delete
        $this->actingAs($admin)
            ->delete(route('admin.deposit-schemes.destroy', $scheme))
            ->assertForbidden();
    }

    public function test_branch_admin_sidebar_does_not_contain_deposit_schemes_link(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee(route('admin.deposit-schemes.index'));
        $response->assertDontSee('Manage Schemes');
    }

    public function test_branch_admin_accessing_superadmin_dashboard_redirects_to_branch_dashboard(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('superadmin.dashboard'));

        $response->assertRedirect(route('branch-admin.dashboard'));
    }

    public function test_branch_admin_sees_consistent_sidebar_on_operational_pages(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('admin.deposits.index'));

        $response->assertOk();
        // Uses branch admin dashboard link and navigation items
        $response->assertSee(route('branch-admin.dashboard'));
        $response->assertSee(route('admin.commissions.overview'));
        $response->assertSee(route('admin.career.overview'));
        // Does not show superadmin dashboard link or superadmin specific links
        $response->assertDontSee(route('superadmin.dashboard'));
        $response->assertDontSee(route('admin.deposit-schemes.index'));
        $response->assertDontSee('Branch &amp; Details', false);
    }

    public function test_branch_admin_cannot_delete_cross_branch_agent(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $uniq = substr(uniqid(), -6);
        $agentB = Agent::create([
            'member_code' => 'AGTB_'.$uniq,
            'agent_id' => 'AGTB_'.$uniq,
            'name' => 'Agent B '.$uniq,
            'email' => 'agent_b_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branchB->id,
            'rank_id' => $this->getDefaultRankId(),
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $this->actingAs($adminA)
            ->delete(route('admin.agents.destroy', $agentB))
            ->assertForbidden();

        $this->assertDatabaseHas('agents', [
            'id' => $agentB->id,
            'status' => 'active',
        ]);
    }

    public function test_branch_admin_cannot_delete_cross_branch_staff(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $pos = StaffPosition::firstOrCreate(
            ['name' => 'Officer'],
            ['code' => 'OFF', 'sort_order' => 1, 'is_active' => true]
        );

        $uniq = uniqid();
        $staffB = Staff::create([
            'employee_id' => 'EMP_B_'.$uniq,
            'name' => 'Staff B '.$uniq,
            'email' => 'staff_b_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branchB->id,
            'position_id' => $pos->id,
            'joining_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($adminA)
            ->delete(route('admin.staff.destroy', $staffB))
            ->assertForbidden();

        $this->assertDatabaseHas('staff', [
            'id' => $staffB->id,
            'status' => 'active',
        ]);
    }

    public function test_branch_admin_cannot_view_cross_branch_or_global_audit_log(): void
    {
        $branchA = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        // Audit logs page is completely removed (returns 404)
        $this->actingAs($adminA)
            ->get('/admin/audit-logs/999')
            ->assertNotFound();
    }

    public function test_branch_admin_cannot_mutate_commission_rules_or_career_levels(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        // Create rule attempt
        $this->actingAs($admin)
            ->get(route('admin.commission-rules.create'))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('admin.commission-rules.store'), [
                'name' => 'Unauthorized Rule',
            ])
            ->assertForbidden();

        // Agent rank create attempt (Super Admin only)
        $this->actingAs($admin)
            ->get(route('superadmin.agent-ranks.create'))
            ->assertForbidden();
    }

    public function test_branch_admin_can_access_notifications_with_branch_scoped_data(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $customerA = $this->createCustomer(['branch_id' => $branchA->id]);
        $depositA = $this->createDeposit([
            'branch_id' => $branchA->id,
            'customer_id' => $customerA->id,
            'status' => Deposit::STATUS_ACTIVE,
            'maturity_date' => now()->addDays(10)->toDateString(),
        ]);

        $customerB = $this->createCustomer(['branch_id' => $branchB->id]);
        $depositB = $this->createDeposit([
            'branch_id' => $branchB->id,
            'customer_id' => $customerB->id,
            'status' => Deposit::STATUS_ACTIVE,
            'maturity_date' => now()->addDays(10)->toDateString(),
        ]);

        $response = $this->actingAs($adminA)->get(route('branch-admin.notifications'));

        $response->assertOk();
        $response->assertSee($depositA->deposit_number);
        $response->assertDontSee($depositB->deposit_number);
    }

    public function test_customer_show_endpoint_is_removed(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);
        $customer = $this->createCustomer(['branch_id' => $branch->id]);

        $response = $this->actingAs($admin)->get("/admin/customers/{$customer->id}");

        $response->assertStatus(404);
    }

    public function test_agent_details_displays_performance(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $sub = substr(uniqid(), -6);
        $agent = Agent::create([
            'member_code' => 'AP_'.$sub,
            'agent_id' => 'AP_'.$sub,
            'name' => 'Agent Perf '.$sub,
            'email' => 'agent_perf_'.$sub.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branch->id,
            'rank_id' => $this->getDefaultRankId(),
            'status' => 'active',
            'joining_date' => now(),
        ]);

        $customer = $this->createCustomer(['branch_id' => $branch->id, 'agent_id' => $agent->id]);

        $response = $this->actingAs($admin)->get(route('admin.agents.show', $agent));

        $response->assertOk();
        $response->assertSee('Active Deposits');
        $response->assertSee('Collections Paid');
        $response->assertSee('Total Commission');
        $response->assertSee('Direct Downline Agents');
    }

    public function test_branch_admin_dashboard_displays_all_seven_operational_metrics(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertSee('Active Advisors');
        $response->assertSee('Total Deposits');
        $response->assertSee('Pending Commissions');
        $response->assertSee('Upcoming Maturities');
        $response->assertSee('Deposit Summary');
        $response->assertSee('Agent Performance');
        $response->assertSee('Recent Branch Activity');
        $response->assertDontSee("Today's Collections", false);
    }

    public function test_branch_admin_sidebar_contains_staff_add_and_manage_options(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertSee(route('admin.staff.index'));
        $response->assertSee(route('admin.staff.create'));
        $response->assertSee('All Staff');
        $response->assertSee('Add Staff');
        $response->assertSee(route('admin.agents.index'));
        $response->assertSee('All Agents');
        $response->assertDontSee(route('admin.agents.create'));
        $response->assertDontSee('Add Agent');
        $response->assertDontSee('New Agent');
    }

    public function test_branch_admin_can_access_agent_create_page(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('admin.agents.create'));

        $response->assertOk();
    }

    public function test_branch_admin_can_store_agent_as_free_field_worker(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);
        $rankId = $this->getDefaultRankId();

        $agentId = 'AG_'.substr(uniqid(), -8);
        $response = $this->actingAs($admin)->post(route('admin.agents.store'), [
            'name' => 'Free Field Worker Test',
            'agent_id' => $agentId,
            'email' => 'agent_'.substr(uniqid(), -8).'@example.com',
            'phone' => '9876543210',
            'rank_id' => $rankId,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('agents', [
            'agent_id' => $agentId,
            'branch_id' => null,
        ]);
    }

    public function test_agent_is_redirected_when_accessing_admin_agent_create_page(): void
    {
        $user = $this->makeBranchAdminWithoutBranch();
        $agentRole = $this->findOrCreateRole('agent', 'Agent');
        $user->roles()->sync([$agentRole->id]);

        $response = $this->actingAs($user)->get(route('admin.agents.create'));

        $response->assertRedirect(route('agent.agents.create'));
    }

    public function test_branch_admin_can_access_staff_index_scoped_to_assigned_branch(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $pos = StaffPosition::firstOrCreate(
            ['name' => 'Officer'],
            ['code' => 'OFF', 'sort_order' => 1, 'is_active' => true]
        );

        $uniqA = substr(uniqid(), -6);
        $staffA = Staff::create([
            'employee_id' => 'EMP_A_'.$uniqA,
            'name' => 'Staff Member A '.$uniqA,
            'email' => 'staff_a_'.$uniqA.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branchA->id,
            'position_id' => $pos->id,
            'joining_date' => now(),
            'status' => 'active',
        ]);

        $uniqB = substr(uniqid(), -6);
        $staffB = Staff::create([
            'employee_id' => 'EMP_B_'.$uniqB,
            'name' => 'Staff Member B '.$uniqB,
            'email' => 'staff_b_'.$uniqB.'@example.com',
            'phone' => '9876543211',
            'branch_id' => $branchB->id,
            'position_id' => $pos->id,
            'joining_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($adminA)->get(route('admin.staff.index'));

        $response->assertOk();
        $response->assertSee($staffA->name);
        $response->assertSee($staffA->employee_id);
        $response->assertDontSee($staffB->name);
        $response->assertDontSee($staffB->employee_id);
    }

    public function test_branch_admin_can_access_staff_create_page(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $response = $this->actingAs($admin)->get(route('admin.staff.create'));

        $response->assertOk();
        $response->assertSee('Add Staff Member');
        $response->assertSee($branch->name);
    }

    public function test_branch_admin_can_create_staff_for_assigned_branch(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $pos = StaffPosition::firstOrCreate(
            ['name' => 'Accountant'],
            ['code' => 'ACC', 'sort_order' => 2, 'is_active' => true]
        );

        $uniq = substr(uniqid(), -6);
        $empId = 'STF_'.strtoupper($uniq);
        $email = 'staff_new_'.$uniq.'@example.com';

        $response = $this->actingAs($admin)->post(route('admin.staff.store'), [
            'name' => 'New Branch Staff '.$uniq,
            'employee_id' => $empId,
            'email' => $email,
            'phone' => '9876543210',
            'branch_id' => $branch->id,
            'position_id' => $pos->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.staff.index'));
        $this->assertDatabaseHas('staff', [
            'employee_id' => $empId,
            'email' => $email,
            'branch_id' => $branch->id,
            'status' => 'active',
        ]);
    }

    public function test_branch_admin_cannot_view_or_edit_cross_branch_staff(): void
    {
        $branchA = $this->createBranch();
        $branchB = $this->createBranch();
        $adminA = $this->makeBranchAdmin($branchA);

        $pos = StaffPosition::firstOrCreate(
            ['name' => 'Officer'],
            ['code' => 'OFF', 'sort_order' => 1, 'is_active' => true]
        );

        $uniq = substr(uniqid(), -6);
        $staffB = Staff::create([
            'employee_id' => 'EMP_CB_'.$uniq,
            'name' => 'Staff Cross '.$uniq,
            'email' => 'staff_cb_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branchB->id,
            'position_id' => $pos->id,
            'joining_date' => now(),
            'status' => 'active',
        ]);

        $this->actingAs($adminA)->get(route('admin.staff.show', $staffB))->assertForbidden();
        $this->actingAs($adminA)->get(route('admin.staff.edit', $staffB))->assertForbidden();
    }

    public function test_branch_admin_can_toggle_own_branch_staff_status(): void
    {
        $branch = $this->createBranch();
        $admin = $this->makeBranchAdmin($branch);

        $pos = StaffPosition::firstOrCreate(
            ['name' => 'Officer'],
            ['code' => 'OFF', 'sort_order' => 1, 'is_active' => true]
        );

        $uniq = substr(uniqid(), -6);
        $staff = Staff::create([
            'employee_id' => 'EMP_TG_'.$uniq,
            'name' => 'Staff Toggle '.$uniq,
            'email' => 'staff_tog_'.$uniq.'@example.com',
            'phone' => '9876543210',
            'branch_id' => $branch->id,
            'position_id' => $pos->id,
            'joining_date' => now(),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->post(route('admin.staff.toggle-status', $staff));

        $response->assertRedirect();
        $this->assertDatabaseHas('staff', [
            'id' => $staff->id,
            'status' => 'inactive',
        ]);
    }
}
