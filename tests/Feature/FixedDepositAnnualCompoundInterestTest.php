<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class FixedDepositAnnualCompoundInterestTest extends TestCase
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
            'name' => 'Super Admin FD Tester',
            'email' => 'fd_tester_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::create([
            'name' => 'FD Test Branch '.uniqid(),
            'code' => 'FDB_'.uniqid(),
            'status' => 'active',
        ]);

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser = User::factory()->create([
            'name' => 'FD Test Agent User',
            'email' => 'agent_'.uniqid().'@multicred.com',
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
            'member_code' => 'AGT_'.rand(1000, 9999),
            'agent_id' => 'AGT_'.rand(1000, 9999),
            'name' => 'FD Test Agent',
            'email' => $agentUser->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUST-'.uniqid(),
            'name' => 'Test FD Investor',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '9876543210',
            'date_of_birth' => '1995-01-01',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    /**
     * Requirement: Verify against this test case:
     * ₹5,000 principal, 10.25% annual interest, 2 years → exact maturity ₹6,077.53; rounded maturity ₹6,078.
     */
    public function test_fd_maturity_calculation_exact_user_test_case(): void
    {
        $principal = 5000.00;
        $annualInterestRate = 10.25;
        $tenureInYears = 2;

        // Exact calculation: 5000 * (1 + 0.1025)^2 = 5000 * 1.21550625 = 6077.53125
        $exactMaturity = Deposit::calculateMaturityAmount(
            $principal,
            $annualInterestRate,
            $tenureInYears,
            'YEARS',
            'FD',
            'ONE_TIME',
            false
        );

        $roundedMaturity = Deposit::calculateMaturityAmount(
            $principal,
            $annualInterestRate,
            $tenureInYears,
            'YEARS',
            'FD',
            'ONE_TIME',
            true
        );

        $this->assertEquals(6077.53, $exactMaturity);
        $this->assertEquals(6078.00, $roundedMaturity);

        // Verify total interest
        $exactInterest = round($exactMaturity - $principal, 2);
        $roundedInterest = round($roundedMaturity - $principal);

        $this->assertEquals(1077.53, $exactInterest);
        $this->assertEquals(1078.00, $roundedInterest);
    }

    /**
     * Test calculation consistency with tenure given in months (24 months = 2 years).
     */
    public function test_fd_maturity_calculation_with_tenure_in_months(): void
    {
        $principal = 5000.00;
        $annualInterestRate = 10.25;
        $tenureInMonths = 24;

        $exactMaturity = Deposit::calculateMaturityAmount(
            $principal,
            $annualInterestRate,
            $tenureInMonths,
            'MONTHS',
            'FD',
            'ONE_TIME'
        );

        $this->assertEquals(6077.53, $exactMaturity);
    }

    /**
     * Test 1-year annual compounding matches 1-year interest.
     */
    public function test_fd_maturity_calculation_for_one_year(): void
    {
        $principal = 10000.00;
        $rate = 8.50;

        $maturity = Deposit::calculateMaturityAmount($principal, $rate, 1, 'YEARS', 'FD', 'ONE_TIME');

        // 10000 * 1.085^1 = 10850.00
        $this->assertEquals(10850.00, $maturity);
    }

    /**
     * Test multi-year compounding dynamically uses scheme configured rate and tenure tiers.
     */
    public function test_multi_year_compounding_with_configured_scheme_tiers(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'FD Compound Growth Scheme',
            'code' => 'FDCG_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.25,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            ['tenure' => 1, 'tenure_unit' => 'YEARS', 'interest_rate' => 9.50, 'senior_citizen_extra_rate' => 0.50],
            ['tenure' => 2, 'tenure_unit' => 'YEARS', 'interest_rate' => 10.25, 'senior_citizen_extra_rate' => 0.50],
            ['tenure' => 3, 'tenure_unit' => 'YEARS', 'interest_rate' => 11.00, 'senior_citizen_extra_rate' => 0.50],
        ]);

        // Create deposit via HTTP endpoint
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), [
                'customer_id' => $this->customer->id,
                'agent_id' => $this->agent->id,
                'branch_id' => $this->branch->id,
                'deposit_scheme_id' => $scheme->id,
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'amount' => 5000.00,
                'start_date' => Carbon::today()->toDateString(),
                'status_action' => 'active',
            ]);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->customer->id)->latest('id')->first();
        $this->assertNotNull($deposit);
        $this->assertEquals(10.25, (float) $deposit->interest_rate);
        $this->assertEquals(6077.53, (float) $deposit->maturity_amount);
        $this->assertEquals(1077.53, (float) $deposit->estimated_interest);
        $this->assertEquals(6078.00, (float) $deposit->rounded_maturity_amount);
        $this->assertEquals(1078.00, (float) $deposit->rounded_estimated_interest);

        // Verify show view displays exact and rounded values
        $showResponse = $this->actingAs($this->superAdmin)->get(route('admin.deposits.show', $deposit));
        $showResponse->assertOk();
        $showResponse->assertSee('6,077.53');
        $showResponse->assertSee('6,078');
        $showResponse->assertSee('1,077.53');
        $showResponse->assertSee('1,078');
    }

    /**
     * Test other deposit types (RD and DD) remain unaffected and use their respective formulas.
     */
    public function test_rd_and_dd_formulas_remain_unaffected(): void
    {
        // RD formula: Simple Interest Product Method
        // P=500, n=12, r=7% -> monthly_interest=2.91, total_interest=2.91*78=226.98, maturity=6226.98
        $rdMaturity = Deposit::calculateMaturityAmount(500.0, 7.0, 12, 'MONTHS', 'RD', 'MONTHLY');
        $this->assertEquals(6226.98, $rdMaturity);

        // DD formula: Daily Pigmy collection
        // Amount: 100/day, 30 days, 8% -> principal 3000, interest 3000 * 0.08 * (30/365) = 19.73 -> 3019.73
        $ddMaturity = Deposit::calculateMaturityAmount(100.0, 8.0, 30, 'DAYS', 'DD', 'DAILY');
        $this->assertEquals(3019.73, $ddMaturity);
    }
}
