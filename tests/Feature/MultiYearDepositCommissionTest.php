<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Commission;
use App\Models\Customer;
use App\Models\CustomerKyc;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeRankCommission;
use App\Models\Lead;
use App\Models\Role;
use App\Models\User;
use App\Services\CommissionService;
use App\Services\FdCommissionSchemeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MultiYearDepositCommissionTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?Branch $branch = null;

    protected ?AgentRank $rankAdvisor = null;

    protected ?AgentRank $rankSenior = null;

    protected ?Agent $agent = null;

    protected ?Customer $customer = null;

    protected CommissionService $commissionService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->commissionService = app(CommissionService::class);

        // Setup Super Admin
        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::create([
            'name' => 'Super Admin Tester',
            'email' => 'sa_mycomm_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        // Setup Branch
        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR_MY_COMM'],
            ['name' => 'Multi-Year Branch', 'status' => 'active']
        );

        // Setup Ranks
        $this->rankAdvisor = AgentRank::firstOrCreate(
            ['level' => 1],
            ['code' => 'ADVISOR_MY', 'name' => 'Advisor']
        );

        $this->rankSenior = AgentRank::firstOrCreate(
            ['level' => 2],
            ['code' => 'SR_ADVISOR_MY', 'name' => 'Senior Advisor']
        );

        // Setup Agent User & Agent Record
        $agentUser = User::create([
            'name' => 'Agent John Multi',
            'email' => 'agent_my_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser->roles()->attach($agentRole->id);

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rankAdvisor->id,
            'agent_id' => 'AGMY'.rand(1000, 9999),
            'agent_code' => 'AGMY'.rand(1000, 9999),
            'name' => 'Agent John Multi',
            'email' => $agentUser->email,
            'phone' => '98'.rand(10000000, 99999999),
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Setup Customer with Verified KYC
        $customerUser = User::create([
            'name' => 'Customer Alice',
            'email' => 'alice_my_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $custRole = Role::firstOrCreate(['slug' => 'customer'], ['name' => 'Customer', 'status' => 'active']);
        $customerUser->roles()->attach($custRole->id);

        $this->customer = Customer::create([
            'user_id' => $customerUser->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'customer_number' => 'CUSTMY'.rand(1000, 9999),
            'name' => 'Customer Alice',
            'phone' => '97'.rand(10000000, 99999999),
            'status' => Customer::STATUS_ACTIVE,
        ]);

        CustomerKyc::create([
            'customer_id' => $this->customer->id,
            'kyc_status' => 'VERIFIED',
            'verified_at' => now(),
            'verified_by' => $this->superAdmin->id,
        ]);
    }

    /**
     * Test 1: Single-year deposit generates single commission record for Year 1.
     */
    public function test_single_year_deposit_generates_single_commission_record(): void
    {
        $scheme = DepositScheme::create([
            'name' => '1 Year Fixed Deposit Plan',
            'code' => 'FD1Y_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'interest_rate' => 7.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Configure Year 1 rank commission: 4.5%
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 4.50,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP1Y_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 50000.00,
            'start_date' => Carbon::today(),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.0,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        $this->assertCount(1, $commissions);
        $comm = $commissions->first();

        $this->assertEquals(1, $comm->payout_year);
        $this->assertEquals(50000.00, (float) $comm->base_amount);
        $this->assertEquals(4.50, (float) $comm->rate);
        $this->assertEquals(2250.00, (float) $comm->commission_amount);
        $this->assertEquals(Commission::STATUS_PENDING, $comm->status);

        $this->assertDatabaseHas('commission_ledgers', [
            'deposit_id' => $deposit->id,
            'agent_id' => $this->agent->id,
            'payout_year' => 1,
            'commission_amount' => 2250.00,
        ]);
    }

    /**
     * Test 2: Multi-year deposit (3 years) receives commission ONLY for exact tenure (Year 3).
     * Never accumulates Year 1 or Year 2 commission.
     */
    public function test_three_year_deposit_generates_exact_three_year_commission_only(): void
    {
        $scheme = DepositScheme::create([
            'name' => '3 Year Cumulative FD Plan',
            'code' => 'FD3Y_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 5000,
            'interest_rate' => 8.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Configure Year 1 = 5.0%, Year 2 = 3.0%, Year 3 = 2.0%
        $rates = [1 => 5.00, 2 => 3.00, 3 => 2.00];
        foreach ($rates as $year => $rate) {
            DepositSchemeRankCommission::create([
                'deposit_scheme_id' => $scheme->id,
                'rank_id' => $this->rankAdvisor->id,
                'payout_year' => $year,
                'commission_percentage' => $rate,
                'is_eligible' => true,
            ]);
        }

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $principal = 100000.00;
        $deposit = Deposit::create([
            'deposit_number' => 'DEP3Y_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => $principal,
            'start_date' => Carbon::today(),
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 8.0,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Critical: Exactly 1 single commission for exact tenure (Year 3 ONLY)
        $this->assertCount(1, $commissions);

        $comm = $commissions->first();
        $this->assertEquals(3, $comm->payout_year);
        $this->assertEquals(2.00, (float) $comm->rate);
        $this->assertEquals(2000.00, (float) $comm->commission_amount);

        // Verify Year 1 and Year 2 were NEVER accumulated or created
        $this->assertEquals(0, Commission::where('deposit_id', $deposit->id)->where('payout_year', 1)->count());
        $this->assertEquals(0, Commission::where('deposit_id', $deposit->id)->where('payout_year', 2)->count());
        $this->assertEquals(1, Commission::where('deposit_id', $deposit->id)->count());
    }

    /**
     * Test 3: Verify exact-tenure commission calculation across all available tenures (1 to 5 years).
     * 1 Year -> 1-year commission only
     * 2 Years -> 2-year commission only
     * 3 Years -> 3-year commission only
     * 4 Years -> 4-year commission only
     * 5 Years -> 5-year commission only
     */
    public function test_exact_tenure_commission_for_all_available_tenures_1_to_5_years(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Universal Flexible FD Plan',
            'code' => 'FD_UNIV_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'interest_rate' => 8.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Configure rates: Year 1 = 3.0%, Year 2 = 3.5%, Year 3 = 4.0%, Year 4 = 4.5%, Year 5 = 5.0%
        $tenureRates = [
            1 => 3.00,
            2 => 3.50,
            3 => 4.00,
            4 => 4.50,
            5 => 5.00,
        ];

        foreach ($tenureRates as $yr => $pct) {
            DepositSchemeRankCommission::create([
                'deposit_scheme_id' => $scheme->id,
                'rank_id' => $this->rankAdvisor->id,
                'payout_year' => $yr,
                'commission_percentage' => $pct,
                'is_eligible' => true,
            ]);
        }

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $amount = 100000.00;

        foreach ($tenureRates as $selectedTenure => $expectedRate) {
            $deposit = Deposit::create([
                'deposit_number' => "DEP_{$selectedTenure}Y_".rand(1000, 9999),
                'customer_id' => $this->customer->id,
                'agent_id' => $this->agent->id,
                'branch_id' => $this->branch->id,
                'deposit_scheme_id' => $scheme->id,
                'deposit_type' => DepositScheme::TYPE_FD,
                'amount' => $amount,
                'start_date' => Carbon::today(),
                'tenure' => $selectedTenure,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 8.0,
                'status' => Deposit::STATUS_APPROVED,
            ]);

            $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

            // Single exact-tenure commission
            $this->assertCount(1, $commissions, "Failed for tenure {$selectedTenure} years: expected exactly 1 commission.");
            $comm = $commissions->first();

            $expectedAmount = round($amount * ($expectedRate / 100), 2);
            $this->assertEquals($selectedTenure, $comm->payout_year, "Commission payout_year does not match exact deposit tenure {$selectedTenure}.");
            $this->assertEquals($expectedRate, (float) $comm->rate, "Commission rate does not match exact rate for Year {$selectedTenure}.");
            $this->assertEquals($expectedAmount, (float) $comm->commission_amount, "Commission amount does not match expected amount for Year {$selectedTenure}.");
        }
    }

    /**
     * Test 4: Respects rank-specific eligibility for the exact tenure year.
     * When Year 5 is ineligible, no commission is awarded for a 5-year deposit.
     */
    public function test_selective_year_eligibility_only_generates_commission_when_exact_tenure_year_is_eligible(): void
    {
        $scheme = DepositScheme::create([
            'name' => '5 Year Long Term Growth Plan',
            'code' => 'FD5Y_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 10000,
            'interest_rate' => 9.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Year 4 is eligible (1.5%), Year 5 is explicitly INELIGIBLE
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 4,
            'commission_percentage' => 1.50,
            'is_eligible' => true,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 5,
            'commission_percentage' => 1.00,
            'is_eligible' => false,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        // 5-Year Deposit (Ineligible for Year 5) -> No commission generated
        $deposit5Y = Deposit::create([
            'deposit_number' => 'DEP5Y_INEL_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000.00,
            'start_date' => Carbon::today(),
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.0,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions5Y = $this->commissionService->calculateAndCreateForDeposit($deposit5Y, $this->superAdmin);
        $this->assertEmpty($commissions5Y);
        $this->assertEquals(0, Commission::where('deposit_id', $deposit5Y->id)->count());

        // 4-Year Deposit (Eligible for Year 4) -> 1 commission generated
        $deposit4Y = Deposit::create([
            'deposit_number' => 'DEP4Y_ELIG_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 100000.00,
            'start_date' => Carbon::today(),
            'tenure' => 4,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.0,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions4Y = $this->commissionService->calculateAndCreateForDeposit($deposit4Y, $this->superAdmin);
        $this->assertCount(1, $commissions4Y);
        $this->assertEquals(4, $commissions4Y->first()->payout_year);
        $this->assertEquals(1500.00, (float) $commissions4Y->first()->commission_amount);
    }

    /**
     * Test 5: Scheme with single-tier commission rule (only Year 1 configured) applies full rate directly as single commission.
     * Does NOT divide the rate by tenure duration.
     */
    public function test_scheme_with_single_tier_commission_applies_full_rate_directly_as_single_commission(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Single-Tier RD Plan',
            'code' => 'RD_ST_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_RD,
            'tenure' => 36, // 36 months = 3 years
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'min_amount' => 500,
            'interest_rate' => 8.5,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Single commission rate configured on Year 1: 6.00%
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 6.00,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $deposit = Deposit::create([
            'deposit_number' => 'DEPRD3Y_ST_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_RD,
            'amount' => 60000.00,
            'start_date' => Carbon::today(),
            'tenure' => 36,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.5,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        // Exactly 1 single commission
        $this->assertCount(1, $commissions);
        $comm = $commissions->first();

        // Full 6.00% rate is applied (not divided into 2%)
        $this->assertEquals(6.00, (float) $comm->rate);
        $this->assertEquals(3600.00, (float) $comm->commission_amount);
    }

    /**
     * Test 6: Idempotency & Duplicate prevention: calling calculateAndCreateForDeposit multiple times produces NO duplicate records.
     */
    public function test_duplicate_prevention_on_multiple_calculation_calls(): void
    {
        $scheme = DepositScheme::create([
            'name' => '2 Year Fixed Deposit',
            'code' => 'FD2Y_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 5000,
            'interest_rate' => 7.5,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 2,
            'commission_percentage' => 3.50,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $deposit = Deposit::create([
            'deposit_number' => 'DEPIDEM_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 50000.00,
            'start_date' => Carbon::today(),
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.5,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        // First execution
        $firstRun = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(1, $firstRun);
        $this->assertEquals(1, Commission::where('deposit_id', $deposit->id)->count());

        // Second execution
        $secondRun = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(1, $secondRun);

        // Database must STILL have exactly 1 record, NO duplicates
        $this->assertEquals(1, Commission::where('deposit_id', $deposit->id)->count());
    }

    /**
     * Test 7: Inactive agent does not get commissions generated.
     */
    public function test_inactive_agent_yields_no_commissions(): void
    {
        $this->agent->update(['status' => 'inactive', 'is_active' => false]);

        $scheme = DepositScheme::create([
            'name' => '1 Year Inactive Test FD',
            'code' => 'FD1Y_INACT_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'interest_rate' => 7.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 5.00,
            'is_eligible' => true,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_INACT_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 50000.00,
            'start_date' => Carbon::today(),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.0,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        $this->assertEmpty($commissions);
        $this->assertEquals(0, Commission::where('deposit_id', $deposit->id)->count());
    }

    /**
     * Test 8: End-to-end deposit store flow via Admin\DepositController generates exact-tenure single commission.
     */
    public function test_deposit_creation_via_controller_generates_exact_tenure_single_commission(): void
    {
        $scheme = DepositScheme::create([
            'name' => '2 Year Growth Deposit',
            'code' => 'FD2YG_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 2,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'interest_rate' => 7.5,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 4.00,
            'is_eligible' => true,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 2,
            'commission_percentage' => 2.50,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $scheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'amount' => 80000.00,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'payment_mode' => 'CASH',
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $this->customer->id)
            ->where('deposit_scheme_id', $scheme->id)
            ->first();

        $this->assertNotNull($deposit);
        // Exactly 1 commission for Year 2
        $this->assertEquals(1, $deposit->commissions()->count());

        $comm = $deposit->commissions()->first();
        $this->assertEquals(2, $comm->payout_year);
        $this->assertEquals(2.50, (float) $comm->rate);
        $this->assertEquals(2000.00, (float) $comm->commission_amount); // 2.5% of 80000
    }

    /**
     * Test 9: Deposit modification via Admin\DepositController reverses previous active commissions and recalculates exact-tenure commission.
     */
    public function test_deposit_modification_reverses_active_commissions_and_recalculates_exact_tenure_commission(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Flexible Modifiable Scheme',
            'code' => 'FD_MOD_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'max_amount' => 1000000,
            'interest_rate' => 7.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Year 1 = 3.0%, Year 3 = 5.0%
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 3.00,
            'is_eligible' => true,
        ]);
        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 3,
            'commission_percentage' => 5.00,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        // 1. Initial 1-year deposit
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_MOD_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 50000.00,
            'start_date' => Carbon::today(),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.0,
            'status' => Deposit::STATUS_PENDING_APPROVAL,
        ]);

        $initialCommissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(1, $initialCommissions);
        $initialComm = $initialCommissions->first();
        $this->assertEquals(1, $initialComm->payout_year);
        $this->assertEquals(1500.00, (float) $initialComm->commission_amount); // 3% of 50000

        // 2. Modify deposit terms to 3 Years and amount to ₹100,000 via controller
        $response = $this->actingAs($this->superAdmin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $scheme->id,
            'agent_id' => $this->agent->id,
            'amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => 'YEARS',
            'start_date' => Carbon::today()->format('Y-m-d'),
            'payment_mode' => 'CASH',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        // Verify deposit updated
        $deposit->refresh();
        $this->assertEquals(3, $deposit->tenure);
        $this->assertEquals(100000.00, (float) $deposit->amount);

        // Previous commission must be marked REJECTED (audit history preserved)
        $initialComm->refresh();
        $this->assertEquals(Commission::STATUS_REJECTED, $initialComm->status);
        $this->assertStringContainsString('modified', $initialComm->rejection_reason);

        // New active commission must be created for Year 3 at 5.0% = ₹5,000
        $activeComms = Commission::where('deposit_id', $deposit->id)
            ->where('status', Commission::STATUS_PENDING)
            ->get();

        $this->assertCount(1, $activeComms);
        $newComm = $activeComms->first();
        $this->assertEquals(3, $newComm->payout_year);
        $this->assertEquals(5.00, (float) $newComm->rate);
        $this->assertEquals(5000.00, (float) $newComm->commission_amount); // 5% of 100000
    }

    /**
     * Test 10: Lead-generating agent attribution priority:
     * When deposit is linked to a lead with an active agent, that lead agent receives the commission.
     */
    public function test_lead_generating_agent_attribution_priority(): void
    {
        // Create second agent as the lead generator
        $leadAgentUser = User::create([
            'name' => 'Lead Gen Agent Bob',
            'email' => 'bob_lead_'.uniqid().'@multicred.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
        ]);
        $leadAgent = Agent::create([
            'user_id' => $leadAgentUser->id,
            'branch_id' => $this->branch->id,
            'rank_id' => $this->rankAdvisor->id,
            'agent_id' => 'AGBOB'.rand(1000, 9999),
            'name' => 'Lead Gen Agent Bob',
            'email' => $leadAgentUser->email,
            'phone' => '91'.rand(10000000, 99999999),
            'joining_date' => now()->toDateString(),
            'status' => 'active',
            'is_active' => true,
        ]);

        // Create lead owned by Bob
        $lead = Lead::create([
            'branch_id' => $this->branch->id,
            'agent_id' => $leadAgent->id,
            'name' => 'Prospective Client',
            'mobile' => '99'.rand(10000000, 99999999),
            'status' => Lead::STATUS_NEW,
        ]);

        $scheme = DepositScheme::create([
            'name' => '1 Year Attribution FD',
            'code' => 'FD1Y_ATTR_'.rand(100, 999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'interest_rate' => 7.0,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 4.00,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        // Deposit created with agent_id = $this->agent->id, but lead_id = $lead->id
        $deposit = Deposit::create([
            'deposit_number' => 'DEP_LEAD_'.rand(1000, 9999),
            'customer_id' => $this->customer->id,
            'agent_id' => $this->agent->id, // Secondary booking agent
            'lead_id' => $lead->id,          // Primary lead owner: Bob
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'deposit_type' => DepositScheme::TYPE_FD,
            'amount' => 50000.00,
            'start_date' => Carbon::today(),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 7.0,
            'status' => Deposit::STATUS_APPROVED,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);

        $this->assertCount(1, $commissions);
        $comm = $commissions->first();

        // Lead generator Bob MUST receive the commission
        $this->assertEquals($leadAgent->id, $comm->agent_id);
        $this->assertEquals(2000.00, (float) $comm->commission_amount);
    }

    /**
     * Test 11: Commissions index displays main deposit details and exact tenure breakdown.
     */
    public function test_commissions_index_page_displays_deposit_and_exact_tenure_breakdown(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'RD 4 Year Special Plan '.uniqid(),
            'code' => 'RD_4Y_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'interest_rate' => 8.00,
            'tenure' => 4,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'min_amount' => 1000,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        for ($yr = 1; $yr <= 4; $yr++) {
            DepositSchemeRankCommission::create([
                'deposit_scheme_id' => $scheme->id,
                'rank_id' => $this->rankAdvisor->id,
                'payout_year' => $yr,
                'commission_percentage' => 1.50,
                'is_eligible' => true,
            ]);
        }

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_RD4Y_'.uniqid(),
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $scheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'amount' => 60000.00,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'maturity_date' => Carbon::today()->addYears(4)->format('Y-m-d'),
            'tenure' => 4,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $commissions = $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertCount(1, $commissions);

        $comm = $commissions->first();
        $this->assertEquals(4, $comm->payout_year);

        $response = $this->actingAs($this->superAdmin)->get(route('admin.commissions.index', ['page' => 1]));

        $response->assertStatus(200);
        // Deposit main row details
        $response->assertSee($deposit->deposit_number);
        $response->assertSee($scheme->name);
        $response->assertSee('4 Yr(s) Tenure');
        $response->assertSee($this->customer->name);
        $response->assertSee($this->agent->name);
        $response->assertSee(number_format($deposit->amount, 2));

        // Year-wise commission breakdown
        $response->assertSee('Year '.$comm->payout_year);
        $response->assertSee($comm->commission_number);
        $response->assertSee(number_format($comm->calculated_amount, 2));
    }

    public function test_admin_can_delete_deposit_and_cascade_commissions(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Delete Test Scheme '.uniqid(),
            'code' => 'SCH_DEL_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.0,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        DepositSchemeRankCommission::create([
            'deposit_scheme_id' => $scheme->id,
            'rank_id' => $this->rankAdvisor->id,
            'payout_year' => 1,
            'commission_percentage' => 2.0,
            'is_eligible' => true,
        ]);

        app(FdCommissionSchemeService::class)->syncCommissionRules($scheme);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_DEL_'.uniqid(),
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $scheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'amount' => 25000.00,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'maturity_date' => Carbon::today()->addYears(1)->format('Y-m-d'),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $this->commissionService->calculateAndCreateForDeposit($deposit, $this->superAdmin);
        $this->assertDatabaseHas('commission_ledgers', ['deposit_id' => $deposit->id]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.deposits.destroy', $deposit));
        $response->assertRedirect(route('admin.deposits.index'));

        $this->assertSoftDeleted('deposits', ['id' => $deposit->id]);
        $this->assertSoftDeleted('commission_ledgers', ['deposit_id' => $deposit->id]);
    }

    public function test_admin_can_delete_lead_and_unlink_deposits(): void
    {
        $lead = Lead::create([
            'lead_code' => 'LD_DEL_'.uniqid(),
            'name' => 'Lead to Delete',
            'mobile' => '98765'.rand(10000, 99999),
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'status' => Lead::STATUS_NEW,
        ]);

        $scheme = DepositScheme::create([
            'name' => 'Lead Deposit Scheme '.uniqid(),
            'code' => 'LD_SCH_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.0,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_LD_'.uniqid(),
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $scheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'lead_id' => $lead->id,
            'amount' => 10000.00,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'maturity_date' => Carbon::today()->addYears(1)->format('Y-m-d'),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('admin.leads.destroy', $lead));
        $response->assertRedirect(route('admin.leads.index'));

        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('deposits', ['id' => $deposit->id, 'lead_id' => null]);
    }

    public function test_superadmin_can_delete_deposit_scheme_with_force(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Temporary Delete Scheme '.uniqid(),
            'code' => 'TMP_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'interest_rate' => 7.50,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $deposit = Deposit::create([
            'deposit_number' => 'DEP_SCH_'.uniqid(),
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $scheme->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'amount' => 15000.00,
            'start_date' => Carbon::today()->format('Y-m-d'),
            'maturity_date' => Carbon::today()->addYears(1)->format('Y-m-d'),
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'status' => Deposit::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)->delete(route('superadmin.fd-commissions.destroy', $scheme), ['force' => 1]);
        $response->assertRedirect(route('superadmin.fd-commissions.index'));

        $this->assertSoftDeleted('deposit_schemes', ['id' => $scheme->id]);
        $this->assertSoftDeleted('deposits', ['id' => $deposit->id]);
    }
}
