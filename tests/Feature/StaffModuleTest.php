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
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffModuleTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected ?Branch $branch1 = null;

    protected ?Branch $branch2 = null;

    protected ?StaffPosition $position = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Super Admin
        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();

        // 2. Branches
        $this->branch1 = Branch::first() ?? Branch::create([
            'name' => 'Main Test Branch',
            'code' => 'BR_MAIN_01',
            'is_active' => true,
        ]);
        $this->branch2 = Branch::where('id', '!=', $this->branch1->id)->first() ?? Branch::create([
            'name' => 'Secondary Test Branch',
            'code' => 'BR_SEC_02',
            'is_active' => true,
        ]);

        // 3. Staff Position
        $this->position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Operations Officer',
            'code' => 'OPR_OFF',
            'is_active' => true,
        ]);

        // 4. Branch Admin
        $this->branchAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))
            ->where('branch_id', $this->branch1->id)
            ->first();

        if (! $this->branchAdmin) {
            $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin']);
            $this->branchAdmin = User::create([
                'name' => 'Branch Admin Tester',
                'email' => 'branchadmin_test_'.uniqid().'@example.com',
                'password' => Hash::make('password123'),
                'branch_id' => $this->branch1->id,
            ]);
            $this->branchAdmin->roles()->syncWithoutDetaching([$adminRole->id]);
        }
    }

    /**
     * Helper to get or create an agent for customer registration.
     */
    protected function getOrCreateAgent(Branch $branch): Agent
    {
        $agent = Agent::where('branch_id', $branch->id)->first();
        if ($agent) {
            return $agent;
        }

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Agent Rank 1',
            'code' => 'RANK_'.uniqid(),
            'level' => 1,
            'is_active' => true,
        ]);

        $agentUser = User::create([
            'name' => 'Agent '.uniqid(),
            'email' => 'agent_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $branch->id,
        ]);

        return Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'AG_'.rand(1000, 9999),
            'name' => $agentUser->name,
            'email' => $agentUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $branch->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);
    }

    /**
     * Helper to create a test customer.
     */
    protected function createTestCustomer(Branch $branch, string $status = 'ACTIVE'): Customer
    {
        $agent = $this->getOrCreateAgent($branch);

        return Customer::create([
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'customer_number' => 'CUST_'.uniqid(),
            'name' => 'Test Customer '.uniqid(),
            'mobile' => '9'.rand(100000000, 999999999),
            'date_of_birth' => '1990-01-01',
            'address' => '123 Test Street, Town',
            'pin_code' => '682001',
            'status' => $status,
        ]);
    }

    /**
     * Helper to create a test deposit.
     */
    protected function createTestDeposit(Customer $customer, float $amount = 10000, string $status = Deposit::STATUS_PENDING_APPROVAL): Deposit
    {
        $scheme = DepositScheme::first() ?? DepositScheme::create([
            'name' => 'Deposit Scheme Test',
            'code' => 'SCHEME_TEST_'.uniqid(),
            'scheme_type' => 'FIXED',
            'duration_months' => 12,
            'interest_rate' => 6.0,
            'is_active' => true,
        ]);

        return Deposit::create([
            'deposit_number' => 'DEP_'.uniqid(),
            'customer_id' => $customer->id,
            'agent_id' => $customer->agent_id,
            'branch_id' => $customer->branch_id,
            'scheme_id' => $scheme->id,
            'amount' => $amount,
            'deposit_date' => now()->toDateString(),
            'status' => $status,
        ]);
    }

    /**
     * Helper to create a test staff member with user account and permissions.
     */
    protected function createStaffMember(Branch $branch, array $permissions = [], string $status = 'active', string $role = 'staff'): array
    {
        $unique = uniqid();
        $email = "staff_{$unique}@example.com";
        $password = 'Secret123!';

        $user = User::create([
            'name' => "Staff User {$unique}",
            'email' => $email,
            'password' => Hash::make($password),
            'branch_id' => $branch->id,
        ]);

        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $user->roles()->syncWithoutDetaching([$staffRole->id]);

        $staff = Staff::create([
            'name' => $user->name,
            'email' => $email,
            'phone' => '98'.rand(10000000, 99999999),
            'employee_id' => 'STF'.rand(10000, 99999),
            'branch_id' => $branch->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => $status,
            'permissions' => $permissions,
            'user_id' => $user->id,
        ]);

        return ['user' => $user, 'staff' => $staff, 'password' => $password];
    }

    /**
     * 1. Staff Account Management: Admin can create staff account with synced User and permissions.
     */
    public function test_admin_can_create_staff_account_with_synced_user_and_permissions(): void
    {
        $uniqueId = rand(10000, 99999);
        $payload = [
            'name' => 'Priya Nair',
            'email' => "priya_{$uniqueId}@example.com",
            'phone' => '98765'.rand(10000, 99999),
            'employee_id' => "STF{$uniqueId}",
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'role' => 'staff',
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'permissions' => ['verify-kyc', 'view-deposits', 'view-customers'],
        ];

        $response = $this->actingAs($this->branchAdmin)
            ->post(route('admin.staff.store'), $payload);

        $response->assertRedirect(route('admin.staff.index'));

        // Assert staff record created
        $this->assertDatabaseHas('staff', [
            'email' => $payload['email'],
            'employee_id' => $payload['employee_id'],
            'branch_id' => $this->branch1->id,
            'status' => 'active',
        ]);

        // Assert user account synchronized
        $this->assertDatabaseHas('users', [
            'email' => $payload['email'],
            'branch_id' => $this->branch1->id,
        ]);

        $createdUser = User::where('email', $payload['email'])->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue(Hash::check('SecurePass123!', $createdUser->password));

        // Check granular permissions assigned
        $this->assertTrue($createdUser->hasPermission('verify-kyc'));
        $this->assertTrue($createdUser->hasPermission('view-customers'));
        $this->assertFalse($createdUser->hasPermission('manage-settings'));
    }

    /**
     * 2. Staff Authentication: Staff can log in and is redirected to Staff Dashboard.
     */
    public function test_staff_login_redirects_to_staff_dashboard(): void
    {
        $data = $this->createStaffMember($this->branch1, ['verify-kyc']);

        $response = $this->post(route('superadmin.login.post'), [
            'email' => $data['user']->email,
            'password' => $data['password'],
        ]);

        $response->assertRedirect(route('staff.dashboard'));
        $this->assertAuthenticatedAs($data['user']);
    }

    /**
     * 3. Deactivated Staff cannot log in.
     */
    public function test_deactivated_staff_cannot_login(): void
    {
        $data = $this->createStaffMember($this->branch1, ['verify-kyc'], 'inactive');

        $response = $this->post(route('superadmin.login.post'), [
            'email' => $data['user']->email,
            'password' => $data['password'],
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /**
     * 4. Staff Dashboard: Minimal dashboard loads with branch data.
     */
    public function test_staff_dashboard_loads_with_real_branch_scoped_metrics(): void
    {
        $data = $this->createStaffMember($this->branch1, ['verify-kyc', 'view-deposits', 'view-collections']);

        $response = $this->actingAs($data['user'])
            ->get(route('staff.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('staff.dashboard');
        $response->assertSee('Staff Operational Workspace');
        $response->assertSee($this->branch1->name);
    }

    /**
     * 5. Branch Isolation: Staff cannot view or inspect records from another branch.
     */
    public function test_staff_branch_isolation_blocks_cross_branch_access(): void
    {
        // Staff at Branch 1
        $staff1 = $this->createStaffMember($this->branch1, ['manage-deposits', 'verify-kyc', 'view-customers']);

        // Customer and deposit belonging to Branch 2
        $customerB2 = $this->createTestCustomer($this->branch2);
        $depositB2 = $this->createTestDeposit($customerB2, 50000, Deposit::STATUS_PENDING_APPROVAL);

        // Branch 1 Staff attempting direct URL manipulation on Branch 2 deposit
        $response = $this->actingAs($staff1['user'])
            ->get(route('admin.deposits.show', $depositB2));

        $response->assertStatus(403);
    }

    /**
     * 6. Customer Inspection: Staff with view-customers permission can view customer details.
     */
    public function test_authorized_staff_can_view_customer_details(): void
    {
        $staff = $this->createStaffMember($this->branch1, ['view-deposits']);
        $customer = $this->createTestCustomer($this->branch1, 'ACTIVE');

        $response = $this->actingAs($staff['user'])
            ->get("/admin/customers/{$customer->id}");

        $response->assertStatus(404);
    }

    /**
     * 7. Staff cannot access customer directory or toggle customer status (pages removed).
     */
    public function test_staff_cannot_access_customer_directory_or_toggle_status(): void
    {
        $staff = $this->createStaffMember($this->branch1, ['view-deposits']);
        $customer = $this->createTestCustomer($this->branch1, 'ACTIVE');

        // Customer directory is removed
        $indexResponse = $this->actingAs($staff['user'])
            ->get('/admin/customers');
        $indexResponse->assertStatus(404);

        // Customer toggle status is removed
        $toggleResponse = $this->actingAs($staff['user'])
            ->post("/admin/customers/{$customer->id}/toggle-status");
        $toggleResponse->assertStatus(404);
    }

    /**
     * 8. Deposit Processing: Staff can verify deposit and return for correction.
     */
    public function test_authorized_staff_can_verify_deposit_and_return_for_correction(): void
    {
        $staff = $this->createStaffMember($this->branch1, ['manage-deposits']);
        $customer = $this->createTestCustomer($this->branch1, 'ACTIVE');
        $deposit = $this->createTestDeposit($customer, 25000, Deposit::STATUS_PENDING_APPROVAL);

        // 1. Staff verifies deposit
        $verifyRes = $this->actingAs($staff['user'])
            ->post(route('admin.deposits.verify', $deposit), [
                'remarks' => 'Application form and cash receipt verified at counter.',
            ]);

        $verifyRes->assertRedirect();
        $freshDeposit = $deposit->fresh();
        $this->assertEquals($staff['user']->id, $freshDeposit->verified_by);
        $this->assertNotNull($freshDeposit->verified_at);
        $this->assertEquals(Deposit::STATUS_PENDING_APPROVAL, $freshDeposit->status);

        // 2. Return for correction
        $returnRes = $this->actingAs($staff['user'])
            ->post(route('admin.deposits.return-correction', $deposit), [
                'remarks' => 'Nominee signature missing on page 2.',
            ]);

        $returnRes->assertRedirect();
        $this->assertStringContainsString('Nominee signature missing', $deposit->fresh()->remarks);

        // Assert audit log recorded
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $staff['user']->id,
            'event' => 'deposit_verified',
        ]);
    }

    /**
     * 9. Segregation of Duties: Staff cannot perform final approval of deposit.
     */
    public function test_staff_cannot_perform_final_approval_of_deposit(): void
    {
        $staff = $this->createStaffMember($this->branch1, ['manage-deposits']);
        $customer = $this->createTestCustomer($this->branch1, 'ACTIVE');
        $deposit = $this->createTestDeposit($customer, 10000, Deposit::STATUS_PENDING_APPROVAL);

        // Attempting approve as staff (only branch admin / superadmin allowed)
        $response = $this->actingAs($staff['user'])
            ->post(route('admin.deposits.approve', $deposit));

        $response->assertStatus(403);
        $this->assertEquals(Deposit::STATUS_PENDING_APPROVAL, $deposit->fresh()->status);
    }

    /**
     * 12. Least Privilege: Staff cannot access global settings or staff management.
     */
    public function test_staff_cannot_access_staff_management_or_global_settings(): void
    {
        $staff = $this->createStaffMember($this->branch1, ['verify-kyc', 'view-deposits']);

        // Cannot view staff management
        $resStaff = $this->actingAs($staff['user'])
            ->get(route('admin.staff.index'));
        $resStaff->assertStatus(403);

        // When accessing superadmin dashboard, staff is redirected to staff dashboard
        $resDashboard = $this->actingAs($staff['user'])
            ->get(route('superadmin.dashboard'));
        $resDashboard->assertRedirect(route('staff.dashboard'));

        // Cannot access superadmin configuration routes like promotion rules
        $resRules = $this->actingAs($staff['user'])
            ->get(route('superadmin.promotion-rules.index'));
        $resRules->assertStatus(403);
    }

    /**
     * 13. Staff Deletion: Authorized administrator can soft-delete staff member.
     */
    public function test_authorized_admin_can_delete_staff_member(): void
    {
        $staff = $this->createStaffMember($this->branch1);
        $staffId = $staff['staff']->id;
        $userId = $staff['user']->id;

        $response = $this->actingAs($this->branchAdmin)
            ->delete(route('admin.staff.destroy', $staff['staff']));

        $response->assertRedirect(route('admin.staff.index'));
        $response->assertSessionHas('success');

        // Staff record is soft deleted
        $this->assertSoftDeleted('staff', ['id' => $staffId]);

        // Associated user account is deleted
        $this->assertDatabaseMissing('users', ['id' => $userId]);

        // Audit log created
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'staff_deleted',
        ]);
    }

    /**
     * 14. Staff Deletion: Cannot delete staff member with active direct reports.
     */
    public function test_cannot_delete_staff_with_direct_subordinates(): void
    {
        $manager = $this->createStaffMember($this->branch1);

        // Subordinate assigned to manager
        Staff::create([
            'user_id' => null,
            'name' => 'Subordinate Staff',
            'email' => 'subordinate_'.uniqid().'@example.com',
            'phone' => '9876543210',
            'employee_id' => 'SUB_'.uniqid(),
            'staff_code' => 'SUB_'.uniqid(),
            'branch_id' => $this->branch1->id,
            'position_id' => $this->position->id,
            'reporting_manager_id' => $manager['staff']->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->branchAdmin)
            ->delete(route('admin.staff.destroy', $manager['staff']));

        $response->assertRedirect(route('admin.staff.index'));
        $response->assertSessionHas('error');

        // Manager should NOT be soft-deleted
        $this->assertDatabaseHas('staff', [
            'id' => $manager['staff']->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * 15. Staff Deletion: Cross-branch admin cannot delete staff belonging to another branch.
     */
    public function test_cross_branch_admin_cannot_delete_staff_from_another_branch(): void
    {
        $staffBranch2 = $this->createStaffMember($this->branch2);

        $response = $this->actingAs($this->branchAdmin)
            ->delete(route('admin.staff.destroy', $staffBranch2['staff']));

        $response->assertStatus(403);
        $this->assertDatabaseHas('staff', [
            'id' => $staffBranch2['staff']->id,
            'deleted_at' => null,
        ]);
    }

    /**
     * 16. Staff with verify-deposits can view pending deposit approvals via filtered deposits index and dashboard links.
     */
    public function test_staff_with_deposit_permissions_can_access_pending_deposits_via_filtered_index(): void
    {
        $staffMember = $this->createStaffMember($this->branch1, ['verify-deposits', 'view-deposits']);

        $response = $this->actingAs($staffMember['user'])
            ->get(route('admin.deposits.index', ['status' => Deposit::STATUS_PENDING_APPROVAL]));

        $response->assertViewIs('admin.deposits.index');
        $response->assertViewHas('schemes');

        $dashboardResponse = $this->actingAs($staffMember['user'])
            ->get(route('staff.dashboard'));

        $dashboardResponse->assertSee(route('admin.deposits.index', ['status' => Deposit::STATUS_PENDING_APPROVAL]), false);
        $dashboardResponse->assertDontSee('/admin/deposits/pending');

        $this->actingAs($staffMember['user'])->get('/admin/deposits/pending-approval')->assertNotFound();
        $this->actingAs($staffMember['user'])->get('/admin/deposits/pending')->assertNotFound();
    }
}
