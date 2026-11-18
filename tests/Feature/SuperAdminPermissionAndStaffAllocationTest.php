<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SuperAdminPermissionAndStaffAllocationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?Branch $branch = null;

    protected ?StaffPosition $position = null;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Super Admin
        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();

        if (! $this->superAdmin) {
            $role = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin']);
            $this->superAdmin = User::create([
                'name' => 'Super Admin Tester',
                'email' => 'superadmin_test_'.uniqid().'@example.com',
                'password' => Hash::make('password123'),
            ]);
            $this->superAdmin->roles()->syncWithoutDetaching([$role->id]);
        }

        // 2. Branch
        $this->branch = Branch::first() ?? Branch::create([
            'name' => 'Test Branch',
            'code' => 'BR_TEST_'.uniqid(),
            'is_active' => true,
        ]);

        // 3. Position
        $this->position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Test Position',
            'code' => 'POS_'.uniqid(),
            'is_active' => true,
        ]);
    }

    /**
     * Test that Super Admin can view the permissions & allocation dashboard.
     */
    public function test_super_admin_can_view_permissions_management_page(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.permissions.index'));

        $response->assertOk();
        $response->assertViewIs('superadmin.permissions.index');
        $response->assertSee('Permissions & Access Control', false);
        $response->assertSee('Add New Permission');
    }

    /**
     * Test that Super Admin can create / add a new operational permission.
     */
    public function test_super_admin_can_add_new_permission(): void
    {
        $permName = 'Test Action '.uniqid();
        $permSlug = 'test-action-'.uniqid();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.permissions.store'), [
                'name' => $permName,
                'slug' => $permSlug,
                'group' => 'deposits',
                'description' => 'Test permission created by super admin',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('permissions', [
            'slug' => $permSlug,
            'name' => $permName,
            'group' => 'deposits',
        ]);
    }

    /**
     * Test that Super Admin can allocate permissions directly to a staff member.
     */
    public function test_super_admin_can_allocate_permissions_to_staff(): void
    {
        $staffUser = User::create([
            'name' => 'Staff Alloc Tester',
            'email' => 'staff_alloc_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $staffUser->roles()->syncWithoutDetaching([$staffRole->id]);

        $staffId = 'STF'.rand(10000, 99999);
        $staff = Staff::create([
            'name' => $staffUser->name,
            'employee_id' => $staffId,
            'staff_code' => $staffId,
            'email' => $staffUser->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'user_id' => $staffUser->id,
            'permissions' => ['view-deposits'],
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.permissions.allocate-staff'), [
                'staff_id' => $staff->id,
                'permissions' => ['view-deposits', 'view-commissions', 'create-deposits'],
            ]);

        $response->assertRedirect();
        $staff->refresh();
        $this->assertEquals(['view-deposits', 'view-commissions', 'create-deposits'], $staff->permissions);
        $this->assertTrue($staffUser->refresh()->hasPermission('view-commissions'));
    }

    /**
     * Test that Super Admin can allocate default permissions to a system role.
     */
    public function test_super_admin_can_allocate_permissions_to_role(): void
    {
        $role = Role::firstOrCreate(['slug' => 'finance'], ['name' => 'Finance']);
        $perm1 = Permission::firstOrCreate(['slug' => 'view-commissions'], ['name' => 'View Commissions', 'group' => 'commissions']);
        $perm2 = Permission::firstOrCreate(['slug' => 'approve-commissions'], ['name' => 'Approve Commissions', 'group' => 'commissions']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.permissions.allocate-role'), [
                'role_id' => $role->id,
                'permissions' => [$perm1->id, $perm2->id],
            ]);

        $response->assertRedirect();
        $role->refresh();
        $this->assertTrue($role->permissions->contains('slug', 'view-commissions'));
        $this->assertTrue($role->permissions->contains('slug', 'approve-commissions'));
    }

    /**
     * Test that Staff dashboard displays properly corresponding to role and allocated permissions.
     */
    public function test_staff_dashboard_reflects_role_and_allocated_permissions(): void
    {
        $staffUser = User::create([
            'name' => 'Staff Dashboard Tester',
            'email' => 'staff_dash_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $staffUser->roles()->syncWithoutDetaching([$staffRole->id]);

        $staffCode = 'STF'.rand(10000, 99999);
        $staff = Staff::create([
            'name' => $staffUser->name,
            'employee_id' => $staffCode,
            'staff_code' => $staffCode,
            'email' => $staffUser->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'user_id' => $staffUser->id,
            'permissions' => ['view-deposits', 'view-commissions'],
        ]);

        $response = $this->actingAs($staffUser)
            ->get(route('staff.dashboard'));

        $response->assertOk();
        $response->assertViewIs('staff.dashboard');
        $response->assertSee('Staff Operational Workspace');
        $response->assertSee('Deposits to Verify');
        $response->assertDontSee('Collections to Verify');
        $response->assertSee('Commissions Pending');
    }

    /**
     * Test that an Agent can log in and access their dedicated operational dashboard.
     */
    public function test_agent_can_access_agent_dashboard(): void
    {
        $agentRank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Agent Rank Test',
            'code' => 'RANK_'.uniqid(),
            'level' => 1,
            'is_active' => true,
        ]);

        $agentUser = User::create([
            'name' => 'Agent Tester',
            'email' => 'agent_dash_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent']);
        $agentUser->roles()->syncWithoutDetaching([$agentRole->id]);

        $agentCode = 'AG'.rand(10000, 99999);
        $agent = Agent::create([
            'name' => $agentUser->name,
            'agent_id' => $agentCode,
            'member_code' => $agentCode,
            'email' => $agentUser->email,
            'phone' => '9876543211',
            'branch_id' => $this->branch->id,
            'rank_id' => $agentRank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'user_id' => $agentUser->id,
        ]);

        $response = $this->actingAs($agentUser)
            ->get(route('agent.dashboard'));

        $response->assertOk();
        $response->assertViewIs('agent.dashboard');
        $response->assertSee('Agent Operational Dashboard');
        $response->assertSee($agent->name);
    }

    /**
     * Test that a staff member allocated NO permissions cannot access or view deposits and commissions.
     */
    public function test_staff_without_permissions_cannot_view_or_access_deposits_or_commissions(): void
    {
        $staffUser = User::create([
            'name' => 'Zero Perm Staff',
            'email' => 'zero_perm_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff']);
        $staffUser->roles()->syncWithoutDetaching([$staffRole->id]);

        $staffCode = 'STF'.rand(10000, 99999);
        $staff = Staff::create([
            'name' => $staffUser->name,
            'employee_id' => $staffCode,
            'staff_code' => $staffCode,
            'email' => $staffUser->email,
            'phone' => '9876543212',
            'branch_id' => $this->branch->id,
            'position_id' => $this->position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'user_id' => $staffUser->id,
            'permissions' => [], // Explicitly zero permissions allocated!
        ]);

        $this->assertFalse($staffUser->hasPermission('view-deposits'));
        $this->assertFalse($staffUser->hasPermission('create-deposits'));
        $this->assertFalse($staffUser->hasPermission('view-commissions'));
        $this->assertFalse($staffUser->hasPermission('approve-commissions'));

        // Staff dashboard hides deposit and commission cards
        $response = $this->actingAs($staffUser)->get(route('staff.dashboard'));
        $response->assertOk();
        $response->assertDontSee('Deposits to Verify');
        $response->assertDontSee('Commissions Pending');
        $response->assertDontSee('Commissions Pending Review');
        $response->assertDontSee('Deposits Pending Verification');

        // Cannot access Deposits routes (all 403 Forbidden)
        $this->actingAs($staffUser)->get(route('admin.deposits.index'))->assertStatus(403);
        $this->actingAs($staffUser)->get(route('admin.deposits.create'))->assertStatus(403);
        $this->actingAs($staffUser)->get(route('admin.deposits.history'))->assertStatus(403);

        // Removed pending deposit queue routes return 404
        $this->actingAs($staffUser)->get('/admin/deposits/pending-approval')->assertNotFound();
        $this->actingAs($staffUser)->get('/admin/deposits/pending')->assertNotFound();

        // Cannot access Commissions routes (all 403 Forbidden)
        $this->actingAs($staffUser)->get(route('admin.commissions.overview'))->assertStatus(403);
        $this->actingAs($staffUser)->get(route('admin.commissions.index'))->assertStatus(403);
        $this->actingAs($staffUser)->get(route('admin.commissions.pending'))->assertStatus(403);
    }

    /**
     * Test that Super Admin can seed and synchronize standard permissions.
     */
    public function test_super_admin_can_seed_default_permissions(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.permissions.seed'));

        $response->assertRedirect(route('superadmin.permissions.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('permissions', ['slug' => 'view-deposits', 'group' => 'deposits']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-commissions', 'group' => 'commissions']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-customers', 'group' => 'customers']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-employees', 'group' => 'staff']);
        $this->assertDatabaseHas('permissions', ['slug' => 'view-ranks', 'group' => 'agents']);

        $adminRole = Role::where('slug', 'admin')->first();
        $this->assertNotNull($adminRole);
        $this->assertTrue($adminRole->permissions->contains('slug', 'view-deposits'));
    }
}
