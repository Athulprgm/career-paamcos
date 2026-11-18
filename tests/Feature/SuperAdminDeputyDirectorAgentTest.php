<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\Branch;
use App\Models\CareerLevel;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SuperAdminDeputyDirectorAgentTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_super_admin_dynamically_receives_deputy_director_agent_profile(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], [
            'name' => 'Super Admin',
            'description' => 'Super Administrator',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Test Dynamically Super Admin',
            'email' => 'test_dyn_superadmin@multicred.com',
            'branch_id' => null,
        ]);
        $user->roles()->attach($superAdminRole->id);

        $agent = $user->ensureSuperAdminAgentProfile();

        $this->assertNotNull($agent);
        $this->assertEquals($user->id, $agent->user_id);
        $this->assertEquals('active', $agent->status);
        $this->assertNull($agent->branch_id);

        $ddRank = CareerLevel::where('level', 12)->orWhere('code', 'DEPUTY_DIRECTOR')->first();
        $this->assertNotNull($ddRank);
        $this->assertEquals((int) $ddRank->id, (int) $agent->rank_id);
    }

    public function test_super_admin_agent_profile_accessor_dynamically_resolves_and_creates_if_missing(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], [
            'name' => 'Super Admin',
            'description' => 'Super Administrator',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Test Accessor Super Admin',
            'email' => 'test_accessor_superadmin@multicred.com',
            'branch_id' => null,
        ]);
        $user->roles()->attach($superAdminRole->id);

        // Before accessing, ensure no agent exists for this user yet
        Agent::where('user_id', $user->id)->delete();

        // Access via dynamic relation property
        $agent = $user->agentProfile;

        $this->assertNotNull($agent);
        $this->assertEquals('active', $agent->status);
        $ddRank = CareerLevel::where('level', 12)->orWhere('code', 'DEPUTY_DIRECTOR')->first();
        $this->assertEquals((int) $ddRank->id, (int) $agent->rank_id);
    }

    public function test_ensure_super_admin_agent_profile_reconciles_rank_if_altered(): void
    {
        $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], [
            'name' => 'Super Admin',
            'description' => 'Super Administrator',
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'name' => 'Test Reconcile Super Admin',
            'email' => 'test_reconcile_superadmin@multicred.com',
            'branch_id' => null,
        ]);
        $user->roles()->attach($superAdminRole->id);

        $agent = $user->ensureSuperAdminAgentProfile();

        // Artificially change rank to rank 1 (Facilitator)
        $rank1 = CareerLevel::where('level', 1)->first();
        $agent->update(['rank_id' => $rank1->id, 'status' => 'inactive']);

        // Call ensure
        $reconciled = $user->ensureSuperAdminAgentProfile();

        $ddRank = CareerLevel::where('level', 12)->orWhere('code', 'DEPUTY_DIRECTOR')->first();
        $this->assertEquals((int) $ddRank->id, (int) $reconciled->rank_id);
        $this->assertEquals('active', $reconciled->status);
    }

    public function test_super_admin_deputy_director_can_source_deposit_and_appears_in_deposit_create(): void
    {
        $superAdmin = User::where('email', 'superadmin@multicred.com')->first();
        if (! $superAdmin) {
            $superAdmin = User::factory()->create(['email' => 'superadmin@multicred.com']);
            $superAdminRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
            $superAdmin->roles()->attach($superAdminRole->id);
        }

        $agent = $superAdmin->ensureSuperAdminAgentProfile();

        $branch = Branch::first() ?? Branch::create([
            'name' => 'Test Head Office Branch',
            'code' => 'THOB',
            'is_active' => true,
        ]);

        $scheme = DepositScheme::active()->first();
        if (! $scheme) {
            $scheme = DepositScheme::create([
                'name' => 'Fixed Deposit 12M Test',
                'code' => 'FD12M_TEST',
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 1000,
                'max_amount' => 10000000,
                'tenure' => 12,
                'tenure_unit' => 'MONTHS',
                'interest_rate' => 8.50,
                'status' => 'active',
            ]);
        }

        $customer = Customer::create([
            'customer_number' => Customer::generateCustomerNumber(),
            'name' => 'Direct SuperAdmin Client',
            'mobile' => '9888877777',
            'customer_type' => 'individual',
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($superAdmin)->get(route('admin.deposits.create'));
        $response->assertStatus(200);
        $response->assertSee($agent->name);
        $response->assertSee('Deputy Director');

        $depositData = [
            'customer_id' => $customer->id,
            'branch_id' => $branch->id,
            'agent_id' => $agent->id,
            'deposit_scheme_id' => $scheme->id,
            'amount' => 100000,
            'start_date' => now()->toDateString(),
            'status' => Deposit::STATUS_ACTIVE,
            'submit_as_active' => 1,
            'payment_mode' => Deposit::PAYMENT_MODE_CASH,
        ];

        $storeResponse = $this->actingAs($superAdmin)->post(route('admin.deposits.store'), $depositData);
        $storeResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('deposits', [
            'customer_id' => $customer->id,
            'agent_id' => $agent->id,
            'branch_id' => $branch->id,
        ]);
    }
}
