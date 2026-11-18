<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Staff;
use App\Models\StaffPosition;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerLoginKycAndAddOptionRemovalTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected User $branchAdmin;

    protected User $agentUser;

    protected User $staffUser;

    protected Branch $branch;

    protected Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Seeded or existing Roles
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'status' => 'active']);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $staffRole = Role::firstOrCreate(['slug' => 'staff'], ['name' => 'Staff', 'status' => 'active']);

        // 2. Branch
        $this->branch = Branch::first() ?? Branch::create([
            'name' => 'Primary Test Branch',
            'code' => 'BR_TEST_'.uniqid(),
            'is_active' => true,
        ]);

        // 3. Super Admin
        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first()
            ?? User::create([
                'name' => 'Super Admin User',
                'email' => 'superadmin_'.uniqid().'@example.com',
                'password' => Hash::make('password123'),
                'branch_id' => null,
            ]);
        $this->superAdmin->roles()->syncWithoutDetaching([$superAdminRole->id]);

        // 4. Branch Admin
        $this->branchAdmin = User::create([
            'name' => 'Branch Admin User',
            'email' => 'branchadmin_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->branchAdmin->roles()->syncWithoutDetaching([$adminRole->id]);

        // 5. Rank & Agent
        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Representative',
            'code' => 'REP_'.uniqid(),
            'level' => 1,
            'is_active' => true,
        ]);

        $this->agentUser = User::create([
            'name' => 'Agent User',
            'email' => 'agent_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->agentUser->roles()->syncWithoutDetaching([$agentRole->id]);

        $this->agent = Agent::create([
            'user_id' => $this->agentUser->id,
            'member_code' => 'AGT_'.rand(10000, 99999),
            'agent_id' => 'AGT_'.rand(10000, 99999),
            'name' => $this->agentUser->name,
            'email' => $this->agentUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
        ]);

        // 6. Staff
        $position = StaffPosition::first() ?? StaffPosition::create([
            'name' => 'Officer',
            'code' => 'OFF_'.uniqid(),
            'is_active' => true,
        ]);

        $this->staffUser = User::create([
            'name' => 'Staff User',
            'email' => 'staff_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'branch_id' => $this->branch->id,
        ]);
        $this->staffUser->roles()->syncWithoutDetaching([$staffRole->id]);

        Staff::create([
            'user_id' => $this->staffUser->id,
            'name' => $this->staffUser->name,
            'email' => $this->staffUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'employee_id' => 'STF_'.rand(1000, 9999),
            'branch_id' => $this->branch->id,
            'position_id' => $position->id,
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'permissions' => ['view-deposits', 'view-collections'],
        ]);
    }

    /**
     * Test 1: Customer accounts cannot log in to the application.
     */
    public function test_customer_account_cannot_log_in(): void
    {
        $customerRole = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer', 'status' => 'active']);

        $customerUser = User::create([
            'name' => 'Customer Depositor',
            'email' => 'customer_'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
        ]);
        $customerUser->roles()->sync([$customerRole->id]);

        $response = $this->post(route('superadmin.login.post'), [
            'login' => $customerUser->email,
            'password' => 'password123',
        ]);

        $response->assertSessionHasErrors();
        $this->assertFalse(Auth::check());
    }

    /**
     * Test 2: Direct access to customer creation returns 404 for all roles.
     */
    public function test_customer_create_endpoint_returns_404_for_all_roles(): void
    {
        // Branch Admin -> 404
        $resBranchAdmin = $this->actingAs($this->branchAdmin)->get('/admin/customers/create');
        $resBranchAdmin->assertStatus(404);

        // Super Admin -> 404
        $resSuperAdmin = $this->actingAs($this->superAdmin)->get('/admin/customers/create');
        $resSuperAdmin->assertStatus(404);

        // Staff -> 404
        $resStaff = $this->actingAs($this->staffUser)->get('/admin/customers/create');
        $resStaff->assertStatus(404);

        // Agent -> 404
        $resAgent = $this->actingAs($this->agentUser)->get('/admin/customers/create');
        $resAgent->assertStatus(404);
    }

    /**
     * Test 3: Customer store (POST) endpoint returns 404.
     */
    public function test_customer_store_endpoint_returns_404(): void
    {
        $payload = [
            'name' => 'Unauthorized Customer',
            'customer_type' => 'individual',
            'mobile' => '9876543210',
            'email' => 'unauth@example.com',
            'date_of_birth' => '1995-01-01',
            'address' => '123 Street',
            'pin_code' => '682001',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
        ];

        $response = $this->actingAs($this->branchAdmin)->post('/admin/customers', $payload);
        $this->assertContains($response->getStatusCode(), [404, 405]);
    }

    /**
     * Test 4: Customer index page is completely removed and returns 404.
     */
    public function test_customer_index_view_is_completely_removed_and_returns_404(): void
    {
        $response = $this->actingAs($this->branchAdmin)->get('/admin/customers');

        $response->assertStatus(404);
    }

    /**
     * Test 5: Branch Admin dashboard does not render New Customer button.
     */
    public function test_branch_admin_dashboard_does_not_contain_new_customer_button(): void
    {
        $response = $this->actingAs($this->branchAdmin)->get(route('branch-admin.dashboard'));

        $response->assertOk();
        $response->assertDontSee('New Customer');
        $response->assertDontSee('admin/customers/create');
    }

    /**
     * Test 6: Customer toggle-status endpoint is completely removed and returns 404.
     */
    public function test_customer_toggle_status_endpoint_is_completely_removed(): void
    {
        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Active Depositor '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'pin_code' => '682001',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->branchAdmin->id,
        ]);

        $response = $this->actingAs($this->branchAdmin)
            ->post("/admin/customers/{$customer->id}/toggle-status");

        $response->assertStatus(404);
    }
}
