<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\FinancialYear;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TargetFieldsCrossModuleFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected AgentRank $rank;

    protected Agent $agent;

    protected function setUp(): void
    {
        parent::setUp();

        $role = Role::where('slug', 'super_admin')->firstOrFail();
        $this->superAdmin = User::create([
            'name' => 'Super Admin User',
            'email' => 'super_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($role->id);

        $this->branch = Branch::create([
            'name' => 'Main Branch '.uniqid(),
            'code' => 'BR'.rand(100, 999),
            'is_active' => true,
        ]);

        $this->rank = AgentRank::where('level', 1)->first() ?? AgentRank::first();
        if ($this->rank) {
            $this->rank->update([
                'minimum_deposit_amount' => 450000.00,
                'min_deposit_amount' => 450000.00,
            ]);
        } else {
            $this->rank = AgentRank::create([
                'name' => 'Field Executive '.uniqid(),
                'code' => 'FE'.rand(10, 99),
                'level' => 1,
                'sort_order' => 1,
                'status' => 'active',
                'minimum_deposit_amount' => 450000.00,
                'min_deposit_amount' => 450000.00,
            ]);
        }

        $agentRole = Role::where('slug', 'agent')->firstOrFail();
        $agentEmail = 'agent_'.uniqid().'@example.com';
        $agentUser = User::create([
            'name' => 'Agent User '.uniqid(),
            'email' => $agentEmail,
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $agentUser->roles()->attach($agentRole->id);

        $code = 'AGT'.rand(1000, 9999);
        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'name' => 'Ravi Kumar '.uniqid(),
            'email' => $agentEmail,
            'password' => bcrypt('password'),
            'phone' => '9876543210',
            'agent_id' => $code,
            'member_code' => $code,
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rank->id,
            'joining_date' => now()->subMonths(6),
            'status' => 'active',
        ]);

        FinancialYear::firstOrCreate(
            ['code' => 'FY2026-27'],
            [
                'name' => 'FY 2026–27',
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
                'status' => 'active',
            ]
        );
    }

    public function test_superadmin_can_create_and_update_agent_rank_with_target_amount(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('superadmin.agent-ranks.store'), [
            'name' => 'Senior Field Officer',
            'sort_order' => 2,
            'status' => 'active',
            'minimum_deposit_amount' => 600000.00,
            'description' => 'Second tier agent rank with 6L baseline target',
        ]);

        $response->assertRedirect(route('superadmin.agent-ranks.index'));

        $this->assertDatabaseHas('ranks', [
            'name' => 'Senior Field Officer',
            'minimum_deposit_amount' => 600000.00,
        ]);

        $createdRank = AgentRank::where('name', 'Senior Field Officer')->first();
        $this->assertNotNull($createdRank);

        $updateResponse = $this->actingAs($this->superAdmin)->put(route('superadmin.agent-ranks.update', $createdRank), [
            'name' => 'Senior Field Officer',
            'sort_order' => 2,
            'status' => 'active',
            'minimum_deposit_amount' => 750000.00,
            'description' => 'Updated baseline target to 7.5L',
        ]);

        $updateResponse->assertRedirect(route('superadmin.agent-ranks.index'));

        $this->assertDatabaseHas('ranks', [
            'id' => $createdRank->id,
            'minimum_deposit_amount' => 750000.00,
        ]);
    }

    public function test_deposit_scheme_stores_and_updates_promotion_target_percentage(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposit-schemes.store'), [
            'name' => 'Special 3Y Fixed Deposit',
            'code' => 'FD-SP-3Y',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 36,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.50,
            'promotion_target_percentage' => 40.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $response->assertRedirect();

        $scheme = DepositScheme::where('code', 'FD-SP-3Y')->first();
        $this->assertNotNull($scheme);
        $this->assertEquals(40.00, (float) $scheme->promotion_target_percentage);

        // Update target credit percentage
        $updateResponse = $this->actingAs($this->superAdmin)->put(route('admin.deposit-schemes.update', $scheme), [
            'name' => 'Special 3Y Fixed Deposit',
            'code' => 'FD-SP-3Y',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 36,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.50,
            'promotion_target_percentage' => 50.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $updateResponse->assertRedirect();
        $scheme->refresh();
        $this->assertEquals(50.00, (float) $scheme->promotion_target_percentage);
    }

    public function test_agent_ranks_index_displays_base_target_and_configurations_link(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('superadmin.agent-ranks.index'));

        $response->assertOk();
        $response->assertSee('Base Deposit Target');
        $response->assertSee('450,000.00');
        $response->assertSee(route('admin.targets.configurations'));
    }

    public function test_deposit_show_and_index_pages_link_to_agent_target_dashboard(): void
    {
        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_number' => 'CUST_'.uniqid(),
            'name' => 'Kavita Sharma',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '98'.rand(10000000, 99999999),
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $scheme = DepositScheme::create([
            'name' => 'FD High Yield Scheme '.uniqid(),
            'code' => 'FD-HY-'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 500,
            'max_amount' => 1000000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'promotion_target_percentage' => 80.00,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP-2026-TEST1',
            'customer_id' => $customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000.00,
            'start_date' => now()->toDateString(),
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        // Test deposit show page cross-links
        $showResponse = $this->actingAs($this->superAdmin)->get(route('admin.deposits.show', $deposit));
        $showResponse->assertOk();
        $showResponse->assertSee(route('admin.targets.agents.show', $this->agent));
        $showResponse->assertSee(route('admin.agents.show', $this->agent));

        // Test deposit index page cross-links
        $indexResponse = $this->actingAs($this->superAdmin)->get(route('admin.deposits.index'));
        $indexResponse->assertOk();
        $indexResponse->assertSee(route('admin.targets.agents.show', $this->agent));
    }

    public function test_career_matrix_and_agent_tree_have_target_links_and_fixed_colspan(): void
    {
        $matrixResponse = $this->actingAs($this->superAdmin)->get(route('admin.career.performance'));
        $matrixResponse->assertOk();
        $matrixResponse->assertSee(route('admin.targets.index'));
        $matrixResponse->assertSee(route('admin.targets.agents.show', $this->agent));
        $matrixResponse->assertDontSee('colspan="10"');

        $treeResponse = $this->actingAs($this->superAdmin)->get(route('admin.agents.index', ['view' => 'tree']));
        $treeResponse->assertOk();
        $treeResponse->assertSee(route('admin.targets.agents.show', $this->agent));
    }
}
