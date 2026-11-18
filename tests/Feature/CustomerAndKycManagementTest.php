<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CustomerAndKycManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?User $branchAdmin = null;

    protected ?Branch $branch = null;

    protected ?Agent $agent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first();
        if (! $this->superAdmin) {
            $this->superAdmin = User::factory()->create();
            $superRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
            $this->superAdmin->roles()->sync([$superRole->id]);
        }

        $this->branch = Branch::first() ?? Branch::create([
            'name' => 'Test Branch',
            'code' => 'BR_'.uniqid(),
            'is_active' => true,
        ]);

        $adminRole = Role::firstOrCreate(['slug' => 'admin'], ['name' => 'Admin', 'status' => 'active']);
        $this->branchAdmin = User::create([
            'name' => 'Branch Admin User',
            'email' => 'badmin_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $this->branchAdmin->roles()->sync([$adminRole->id]);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Rank 1',
            'code' => 'R1_'.uniqid(),
            'level' => 1,
            'is_active' => true,
        ]);

        $this->agent = Agent::create([
            'member_code' => 'AGT_'.uniqid(),
            'agent_id' => 'AGT_'.uniqid(),
            'name' => 'Agent Name',
            'email' => 'agent_'.uniqid().'@example.com',
            'phone' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'joining_date' => now(),
            'status' => 'active',
        ]);
    }

    /**
     * Test 1: Customer index route is removed and returns 404.
     */
    public function test_customer_index_route_is_removed_and_returns_404(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/customers');
        $response->assertStatus(404);

        $branchResponse = $this->actingAs($this->branchAdmin)->get('/admin/customers');
        $branchResponse->assertStatus(404);
    }

    /**
     * Test 2: Customer create route is removed and returns 404.
     */
    public function test_customer_create_route_is_removed_and_returns_404(): void
    {
        $response = $this->actingAs($this->superAdmin)->get('/admin/customers/create');
        $response->assertStatus(404);

        $branchResponse = $this->actingAs($this->branchAdmin)->get('/admin/customers/create');
        $branchResponse->assertStatus(404);
    }

    /**
     * Test 3: Customer store route is removed and returns 404.
     */
    public function test_customer_store_route_is_removed_and_returns_404(): void
    {
        $payload = [
            'name' => 'New Depositor',
            'customer_type' => 'individual',
            'mobile' => '9876543210',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
        ];

        $response = $this->actingAs($this->superAdmin)->post('/admin/customers', $payload);
        $response->assertStatus(404);
    }

    /**
     * Test 4: Customer show route is removed and returns 404.
     */
    public function test_customer_show_route_is_removed_and_returns_404(): void
    {
        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Depositor '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->get("/admin/customers/{$customer->id}");
        $response->assertStatus(404);

        $branchResponse = $this->actingAs($this->branchAdmin)->get("/admin/customers/{$customer->id}");
        $branchResponse->assertStatus(404);
    }

    /**
     * Test 5: Customer edit route is removed and returns 404.
     */
    public function test_customer_edit_route_is_removed_and_returns_404(): void
    {
        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Depositor '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->get("/admin/customers/{$customer->id}/edit");
        $response->assertStatus(404);
    }

    /**
     * Test 6: Customer toggle-status route is removed and returns 404.
     */
    public function test_customer_toggle_status_route_is_removed_and_returns_404(): void
    {
        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Depositor '.uniqid(),
            'customer_type' => 'individual',
            'mobile' => '98'.rand(10000000, 99999999),
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->post("/admin/customers/{$customer->id}/toggle-status");
        $response->assertStatus(404);
    }
}
