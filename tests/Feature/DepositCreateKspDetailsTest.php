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
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositCreateKspDetailsTest extends TestCase
{
    use DatabaseTransactions;

    protected User $superAdmin;

    protected Branch $branch;

    protected Agent $agent;

    protected Customer $seniorCustomer;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'KSP Deposit Tester',
            'email' => 'ksp_deposit_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::create([
            'name' => 'KSP Test Branch '.uniqid(),
            'code' => 'KTB_'.uniqid(),
            'status' => 'active',
        ]);

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser = User::factory()->create([
            'name' => 'KSP Test Agent User',
            'email' => 'ksp_agent_'.uniqid().'@multicred.com',
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
            'member_code' => 'AGK_'.rand(1000, 9999),
            'agent_id' => 'AGK_'.rand(1000, 9999),
            'name' => 'KSP Test Agent',
            'email' => $agentUser->email,
            'phone' => '9876501234',
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);

        $this->seniorCustomer = Customer::create([
            'customer_number' => 'CUST-KSP-'.uniqid(),
            'name' => 'KSP Senior Farmer',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '9876509999',
            'date_of_birth' => '1955-01-01',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);
    }

    protected function createKspScheme(int $periodYears, float $insuranceCoverage = 300000.00, ?string $code = null): DepositScheme
    {
        $schemeCode = $code ?? 'KSP-T'.$periodYears.'Y'.rand(10, 99);
        $config = DepositScheme::getKspConfigForPeriod($periodYears);
        $config['insurance_coverage'] = $insuranceCoverage;

        return DepositScheme::updateOrCreate(
            ['code' => $schemeCode],
            [
                'name' => "Kissan Samrudhi Plan (KSP) {$periodYears} Year",
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 15000.00,
                'max_amount' => null,
                'tenure' => $periodYears,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 0.00,
                'senior_citizen_extra_rate' => 0.00,
                'is_double' => false,
                'allow_premature_withdrawal' => false,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'interest_payout_frequency' => DepositScheme::PAYOUT_ANNUALLY,
                'ksp_configuration' => $config,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]
        );
    }

    public function test_deposit_create_page_displays_ksp_insurance_coverage_and_accurate_ksp_details(): void
    {
        $ksp1Year = $this->createKspScheme(1, 300000.00, 'KSP1Y');
        $kspCustom = $this->createKspScheme(5, 500000.00, 'KSP-C5Y');

        $response = $this->actingAs($this->superAdmin)->get(route('admin.deposits.create'));

        $response->assertOk();
        $response->assertSee('id="insurance_coverage_col"', false);
        $response->assertSee('id="insurance_coverage_display"', false);
        $response->assertSee('id="ksp_details_panel"', false);
        $response->assertSee('id="preview_ksp_rows"', false);

        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new DOMXPath($dom);

        $ksp1Option = $xpath->query("//*[@id='deposit_scheme_id']/option[@value='{$ksp1Year->id}']")->item(0);
        $this->assertNotNull($ksp1Option);
        $this->assertSame('1', $ksp1Option->getAttribute('data-is-ksp'));
        $this->assertStringContainsString('Insurance: ₹3,00,000', $ksp1Option->textContent);
        $this->assertStringContainsString('Return: ₹45,000/yr (11th Year)', $ksp1Option->textContent);
        $this->assertStringNotContainsString('0.00% p.a.', $ksp1Option->textContent);

        $kspCustomOption = $xpath->query("//*[@id='deposit_scheme_id']/option[@value='{$kspCustom->id}']")->item(0);
        $this->assertNotNull($kspCustomOption);
        $this->assertSame('1', $kspCustomOption->getAttribute('data-is-ksp'));
        $this->assertStringContainsString('Insurance: ₹5,00,000', $kspCustomOption->textContent);
        $this->assertStringContainsString('Return: ₹45,000/yr (Yr 11–15)', $kspCustomOption->textContent);
        $this->assertStringNotContainsString('0.00% p.a.', $kspCustomOption->textContent);
    }

    public function test_storing_ksp_deposit_calculates_exact_total_return_ignores_senior_rate_and_shows_insurance_details(): void
    {
        $ksp10Year = $this->createKspScheme(10, 300000.00, 'KSP10Y');

        $response = $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $ksp10Year->id,
            'amount' => 30000.00,
            'tenure' => 10,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-11-16',
            'is_senior_citizen' => 1,
            'is_double' => 1,
            'status_action' => 'active',
        ]);

        $deposit = Deposit::where('deposit_scheme_id', $ksp10Year->id)->latest('id')->firstOrFail();
        $response->assertRedirect(route('admin.deposits.show', $deposit));

        // 30,000/yr = 2x base (15,000/yr) => 90,000/yr return across Yr 11..20 (10 payouts) = 9,00,000.00
        // Total deposited = 30,000 * 10 = 3,00,000.00 => Net gain = 6,00,000.00
        $this->assertTrue($deposit->isKsp());
        $this->assertFalse($deposit->isDouble());
        $this->assertFalse((bool) $deposit->is_senior_citizen);
        $this->assertSame(0.0, (float) $deposit->interest_rate);
        $this->assertSame(10, (int) $deposit->tenure);
        $this->assertSame('2036-11-16', $deposit->maturity_date?->format('Y-m-d'));
        $this->assertSame(900000.0, (float) $deposit->maturity_amount);
        $this->assertSame(600000.0, (float) $deposit->estimated_interest);

        $closureCheck = $deposit->calculatePrematureClosureDetails();
        $this->assertFalse($closureCheck['allowed']);

        $showResponse = $this->actingAs($this->superAdmin)->get(route('admin.deposits.show', $deposit));
        $showResponse->assertOk();
        $showResponse->assertSee('id="ksp_deposit_details_card"', false);
        $showResponse->assertSee('Insurance Coverage');
        $showResponse->assertSee('3,00,000.00');
        $showResponse->assertSee('9,00,000.00');
        $showResponse->assertSee('90,000.00');
        $showResponse->assertSee('11th Year to 20th Year');
    }

    public function test_storing_and_updating_one_year_ksp_deposit_computes_single_11th_year_payout(): void
    {
        $ksp1Year = $this->createKspScheme(1, 300000.00, 'KSP1Y');

        $this->actingAs($this->superAdmin)->post(route('admin.deposits.store'), [
            'customer_id' => $this->seniorCustomer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $ksp1Year->id,
            'amount' => 15000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-11-16',
        ])->assertRedirect();

        $deposit = Deposit::where('deposit_scheme_id', $ksp1Year->id)->latest('id')->firstOrFail();

        $this->assertSame(45000.0, (float) $deposit->maturity_amount);
        $this->assertSame(30000.0, (float) $deposit->estimated_interest);

        $editResponse = $this->actingAs($this->superAdmin)->get(route('admin.deposits.edit', $deposit));
        $editResponse->assertOk();
        $editResponse->assertSee('data-is-ksp="1"', false);
        $editResponse->assertSee('Insurance: ₹3,00,000', false);

        $this->actingAs($this->superAdmin)->put(route('admin.deposits.update', $deposit), [
            'deposit_scheme_id' => $ksp1Year->id,
            'agent_id' => $this->agent->id,
            'amount' => 30000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'start_date' => '2026-11-16',
            'is_senior_citizen' => 1,
        ])->assertRedirect(route('admin.deposits.show', $deposit));

        $deposit->refresh();
        $this->assertSame(90000.0, (float) $deposit->maturity_amount);
        $this->assertSame(60000.0, (float) $deposit->estimated_interest);
        $this->assertFalse((bool) $deposit->is_senior_citizen);
    }
}
