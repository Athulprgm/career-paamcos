<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeMrpConfiguration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositCreateSchemeDifferentiationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?Branch $branch = null;

    protected ?Agent $agent = null;

    protected ?Customer $customer = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin MRP Tester',
            'email' => 'mrp_diff_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::create([
            'name' => 'Diff Test Branch '.uniqid(),
            'code' => 'DTB_'.uniqid(),
            'status' => 'active',
        ]);

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser = User::factory()->create([
            'name' => 'Diff Test Agent User',
            'email' => 'agent_diff_'.uniqid().'@multicred.com',
            'branch_id' => $this->branch->id,
        ]);
        $agentUser->roles()->attach($agentRole->id);

        $rank = AgentRank::first() ?? AgentRank::create([
            'name' => 'Advisor',
            'level' => 1,
            'min_business' => 0,
        ]);

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'AGD_'.rand(1000, 9999),
            'agent_id' => 'AGD_'.rand(1000, 9999),
            'name' => 'Diff Test Agent',
            'email' => $agentUser->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUST-DIFF-'.uniqid(),
            'name' => 'Test Scheme Customer',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '9876543211',
            'date_of_birth' => '1992-05-15',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    protected function createScheme(array $overrides = []): DepositScheme
    {
        return DepositScheme::create(array_merge([
            'name' => 'FD Test Plan '.uniqid(),
            'code' => 'TST'.rand(10000, 99999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 5000.00,
            'max_amount' => null,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.25,
            'is_double' => false,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ], $overrides));
    }

    public function test_get_rates_endpoint_returns_distinct_metadata_for_mrp_versus_other_schemes(): void
    {
        $mrpScheme = $this->createScheme([
            'name' => 'Monthly Return Plan (MRP) 3 Year',
            'code' => 'MRP3Y'.rand(100, 999),
            'scheme_type' => 'MIS',
            'min_amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_MONTHLY,
        ]);
        $mrpScheme->syncMrpConfiguration([
            'is_enabled' => true,
            'contribution_amount' => 100000.00,
            'min_tenure' => 1,
            'max_tenure' => 3,
            'tenure_unit' => 'YEARS',
            'calculation_method' => DepositSchemeMrpConfiguration::METHOD_STANDARD,
        ], [
            ['year' => 1, 'general_rate' => 10.00, 'senior_rate' => 10.50],
            ['year' => 3, 'general_rate' => 11.00, 'senior_rate' => 11.50],
        ]);

        $rdScheme = $this->createScheme([
            'name' => 'RD Recurring Plan',
            'code' => 'RDT'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => 500.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.00,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
        ]);

        $doubleScheme = $this->createScheme([
            'name' => 'Double Plan 6Y',
            'code' => 'DBLT'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_DOUBLE,
            'min_amount' => 5000.00,
            'tenure' => 6,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 0.00,
            'senior_citizen_extra_rate' => 0.00,
            'is_double' => true,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
        ]);

        $mrpResp = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rates', $mrpScheme));

        $mrpResp->assertOk()
            ->assertJson([
                'success' => true,
                'scheme_id' => $mrpScheme->id,
                'scheme_type' => 'MIS',
                'collection_frequency' => 'ONE_TIME',
                'interest_payout_frequency' => 'MONTHLY',
                'is_double' => false,
                'is_mrp' => true,
                'mrp_calculation_method' => DepositSchemeMrpConfiguration::METHOD_STANDARD,
                'min_amount' => 100000.0,
            ])
            ->assertJsonPath('mrp_configuration.is_enabled', true)
            ->assertJsonCount(2, 'mrp_configuration.rates');

        $rdResp = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rates', $rdScheme));

        $rdResp->assertOk()
            ->assertJson([
                'success' => true,
                'scheme_id' => $rdScheme->id,
                'scheme_type' => 'RD',
                'collection_frequency' => 'MONTHLY',
                'interest_payout_frequency' => 'AT_MATURITY',
                'is_double' => false,
                'is_mrp' => false,
                'mrp_configuration' => null,
            ]);

        $doubleResp = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rates', $doubleScheme));

        $doubleResp->assertOk()
            ->assertJson([
                'success' => true,
                'scheme_id' => $doubleScheme->id,
                'is_double' => true,
                'is_mrp' => false,
                'collection_frequency' => 'ONE_TIME',
                'interest_payout_frequency' => 'AT_MATURITY',
            ]);
    }

    public function test_deposit_create_view_embeds_distinct_scheme_attributes_and_dynamic_containers(): void
    {
        $mrpScheme = $this->createScheme([
            'name' => 'Monthly Return Plan Special',
            'code' => 'MRPV'.rand(100, 999),
            'scheme_type' => 'MIS',
            'min_amount' => 100000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_MONTHLY,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.create'));

        $response->assertOk();
        $response->assertSee($mrpScheme->name, false);
        $response->assertSee('data-is-mrp="1"', false);
        $response->assertSee('data-payout-frequency="MONTHLY"', false);
        $response->assertSee('name="payout_frequency"', false);
        $response->assertSee('id="scheme_info_box"', false);
        $response->assertSee('id="preview_mrp_rows"', false);
        $response->assertSee('resolveSchemeProfile', false);
    }

    public function test_creating_mrp_deposit_persists_monthly_payout_and_simple_return_total(): void
    {
        $mrpScheme = $this->createScheme([
            'name' => 'Monthly Return Plan (MRP) 3 Year',
            'code' => 'MRP3P'.rand(100, 999),
            'scheme_type' => 'MIS',
            'min_amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_MONTHLY,
        ]);

        // General citizen 3-year MRP: 1,00,000 @ 10% simple interest over 3 years = 1,30,000.00 (NOT compounded 1,33,100)
        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $mrpScheme->id,
            'amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-04-01',
            'is_senior_citizen' => 0,
        ]);

        $response->assertRedirect();

        $generalDeposit = Deposit::where('deposit_scheme_id', $mrpScheme->id)
            ->where('is_senior_citizen', false)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame('MIS', $generalDeposit->deposit_type);
        $this->assertSame(Deposit::PAYOUT_MONTHLY, $generalDeposit->payout_frequency);
        $this->assertSame(DepositScheme::FREQ_ONE_TIME, $generalDeposit->collection_frequency);
        $this->assertSame(130000.00, (float) $generalDeposit->maturity_amount);
        $this->assertSame(30000.00, (float) $generalDeposit->estimated_interest);
        $this->assertTrue($generalDeposit->isMrp());
        $this->assertSame(833.33, $generalDeposit->getMonthlyReturnAmount());

        // Senior citizen 1-year MRP: 1,00,000 @ 10.50% -> Monthly Return 875.00, Maturity Benefit 1,10,500.00
        $mrp1YearScheme = $this->createScheme([
            'name' => 'Monthly Return Plan (MRP) 1 Year Senior',
            'code' => 'MRP1S'.rand(100, 999),
            'scheme_type' => 'MIS',
            'min_amount' => 100000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_MONTHLY,
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $mrp1YearScheme->id,
            'amount' => 100000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-04-01',
            'is_senior_citizen' => 1,
            'payout_frequency' => Deposit::PAYOUT_MONTHLY,
        ])->assertRedirect();

        $seniorDeposit = Deposit::where('deposit_scheme_id', $mrp1YearScheme->id)
            ->where('is_senior_citizen', true)
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(10.50, (float) $seniorDeposit->interest_rate);
        $this->assertSame(110500.00, (float) $seniorDeposit->maturity_amount);
        $this->assertSame(875.00, $seniorDeposit->getMonthlyReturnAmount());
    }

    public function test_fd_3_year_still_uses_annual_compounding_unlike_mrp(): void
    {
        $fdScheme = $this->createScheme([
            'name' => 'FD 3 Year Compound',
            'code' => 'FD3C'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 5000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'senior_citizen_extra_rate' => 0.25,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
        ]);

        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $fdScheme->id,
            'amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-04-01',
            'is_senior_citizen' => 0,
        ])->assertRedirect();

        $fdDeposit = Deposit::where('deposit_scheme_id', $fdScheme->id)->latest('id')->firstOrFail();

        $this->assertSame(Deposit::PAYOUT_AT_MATURITY, $fdDeposit->payout_frequency);
        $this->assertSame(133100.00, (float) $fdDeposit->maturity_amount);
        $this->assertFalse($fdDeposit->isMrp());
    }

    public function test_updating_deposit_scheme_from_fd_to_mrp_updates_payout_frequency_and_maturity(): void
    {
        $fdScheme = $this->createScheme([
            'name' => 'FD 2 Year',
            'code' => 'FD2U'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 5000.00,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
        ]);

        $mrpScheme = $this->createScheme([
            'name' => 'Monthly Return Plan (MRP) 2 Year',
            'code' => 'MRP2U'.rand(100, 999),
            'scheme_type' => 'MIS',
            'min_amount' => 100000.00,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_MONTHLY,
        ]);

        $deposit = Deposit::create([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $fdScheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000.00,
            'start_date' => '2026-04-01',
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'payout_frequency' => Deposit::PAYOUT_AT_MATURITY,
            'status' => Deposit::STATUS_DRAFT,
            'created_by' => $this->superAdmin->id,
        ]);

        // Initial FD compound maturity: 100000 * 1.10^2 = 121000.00
        $this->assertSame(121000.00, (float) $deposit->maturity_amount);

        // Switch scheme to 2-year MRP without explicitly passing payout_frequency
        $this->actingAs($this->superAdmin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $mrpScheme->id,
            'amount' => 100000.00,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-04-01',
            'agent_id' => $this->agent->id,
            'is_senior_citizen' => 0,
        ])->assertRedirect();

        $deposit->refresh();

        $this->assertSame('MIS', $deposit->deposit_type);
        $this->assertSame(Deposit::PAYOUT_MONTHLY, $deposit->payout_frequency);
        // 2-year MRP simple interest: 100000 + (100000 * 10% * 2) = 120000.00
        $this->assertSame(120000.00, (float) $deposit->maturity_amount);
    }
}
