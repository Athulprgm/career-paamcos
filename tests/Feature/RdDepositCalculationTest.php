<?php

namespace Tests\Feature;

use App\Models\Agent;
use App\Models\AgentRank;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerKyc;
use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

/**
 * RD Installment Validation, Interest Calculation, and Projected Summary Tests.
 *
 * Approved Formula:
 * r = Annual Interest Rate / 1200
 * Maturity = P × ((1 + r)^n − 1) / r
 * Principal = P × n
 * Interest = Maturity − Principal
 *
 * This is the monthly-compounding ordinary annuity formula used in cooperative
 * society RD schemes and implemented in Deposit::calculateMaturityAmount() for type=RD.
 */
class RdDepositCalculationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?Branch $branch = null;

    protected ?Agent $agent = null;

    protected ?Customer $customer = null;

    protected ?DepositScheme $rdScheme = null;

    protected ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'SA_RD_'.uniqid(),
            'email' => 'sa_rd_'.uniqid().'@multicred.test',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $rank = AgentRank::first();

        $this->branch = Branch::firstOrCreate(
            ['code' => 'RD_TEST_BR'],
            ['name' => 'RD Test Branch', 'is_active' => true]
        );

        $agentUser = User::factory()->create([
            'email' => 'rd_agent_'.uniqid().'@test.com',
            'branch_id' => $this->branch->id,
        ]);
        $agentUser->roles()->attach($agentRole->id);

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'RDA_'.rand(1000, 9999),
            'agent_id' => 'RDA_'.rand(1000, 9999),
            'name' => 'RD Agent '.uniqid(),
            'email' => $agentUser->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'status' => 'active',
            'rank_id' => $rank?->id,
            'joining_date' => now()->toDateString(),
        ]);

        $this->customer = Customer::create([
            'customer_number' => 'CUSTRD_'.uniqid(),
            'name' => 'RD Customer '.uniqid(),
            'customer_type' => 'individual',
            'email' => 'rdcust_'.uniqid().'@test.com',
            'mobile' => '9876543211',
            'date_of_birth' => '1990-01-01',
            'address' => 'Test Address',
            'pin_code' => '682001',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
            'created_by' => $this->superAdmin->id,
        ]);

        CustomerKyc::create([
            'customer_id' => $this->customer->id,
            'id_type' => 'AADHAR',
            'id_number' => 'AADHRD'.rand(100000, 999999),
            'address' => 'Test Address',
            'status' => CustomerKyc::STATUS_APPROVED,
            'submitted_at' => now(),
            'verified_at' => now(),
        ]);

        // RD scheme: min=500, max=10000, tenure=12 months, rate=7%, monthly
        $this->rdScheme = DepositScheme::create([
            'name' => 'Test RD Scheme '.uniqid(),
            'code' => 'RD_TST_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => 500.00,
            'max_amount' => 10000.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);
    }

    // =======================================================================
    //  1. RD INSTALLMENT AMOUNT VALIDATION (backend server-side)
    // =======================================================================

    /** @test */
    public function test_rejects_installment_below_scheme_minimum(): void
    {
        // Scheme min=500; submitting 100 must be rejected
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 100]));

        $response->assertSessionHasErrors('amount');
        $errorMsg = session('errors')->get('amount')[0] ?? '';
        $this->assertStringContainsString('100.00', $errorMsg);
        $this->assertStringContainsString('500.00', $errorMsg);
    }

    /** @test */
    public function test_rejects_installment_of_zero(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 0]));

        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function test_accepts_installment_exactly_at_scheme_minimum(): void
    {
        // Scheme min=500; submitting exactly 500 must be accepted
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 500]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('deposits', [
            'customer_id' => $this->customer->id,
            'deposit_scheme_id' => $this->rdScheme->id,
        ]);
    }

    /** @test */
    public function test_accepts_installment_above_scheme_minimum(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 1000]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    /** @test */
    public function test_rejects_installment_above_scheme_maximum(): void
    {
        // Scheme max=10000; submitting 15000 must be rejected
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 15000]));

        $response->assertSessionHasErrors('amount');
        $errorMsg = session('errors')->get('amount')[0] ?? '';
        $this->assertStringContainsString('15,000.00', $errorMsg);
        $this->assertStringContainsString('10,000.00', $errorMsg);
    }

    /** @test */
    public function test_accepts_installment_exactly_at_scheme_maximum(): void
    {
        // Scheme max=10000; submitting exactly 10000 must be accepted
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 10000]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();
    }

    // =======================================================================
    //  2. TOTAL SCHEDULED PRINCIPAL & SIMPLE INTEREST PRODUCT METHOD
    // =======================================================================

    /** @test */
    public function test_calculates_correct_total_scheduled_principal_for_rd(): void
    {
        // P=500, n=12, r=7%
        // monthly_interest = floor((500 * 0.07 / 12) * 100) / 100 = 2.91
        // total_interest = 2.91 * (12 * 13 / 2) = 2.91 * 78 = 226.98
        // total_principal = 500 * 12 = 6000.00
        // maturity_value = 6000.00 + 226.98 = 6226.98
        $maturity = Deposit::calculateMaturityAmount(500.0, 7.0, 12, 'MONTHS', 'RD', 'MONTHLY');

        $principal = 500.0 * 12; // 6000.00
        $interest = round($maturity - $principal, 2); // 226.98

        $this->assertEquals(6000.00, $principal);
        $this->assertEquals(6226.98, $maturity);
        $this->assertEquals(226.98, $interest);
    }

    /** @test */
    public function test_calculates_rd_simple_interest_product_method_with_truncation_and_schedule(): void
    {
        // P=500, n=12, r=10%
        // monthly_interest = (500 * 0.10) / 12 = 4.1666... -> truncated to 4.16
        // total_interest = 4.16 * 78 = 324.48
        // total_principal = 500 * 12 = 6000.00
        // maturity_value = 6324.48
        $breakdown = Deposit::calculateRdSimpleInterest(500.0, 12, 10.0, true);

        $this->assertEquals(4.16, $breakdown['monthly_interest']);
        $this->assertEquals(78, $breakdown['sum_of_months']);
        $this->assertEquals(6000.00, $breakdown['total_principal']);
        $this->assertEquals(324.48, $breakdown['total_interest']);
        $this->assertEquals(6324.48, $breakdown['maturity_value']);
        $this->assertCount(12, $breakdown['schedule']);

        // Month 1: installment=500, cumulative_principal=500, months_invested=12, monthly_interest_accrued=4.16, installment_interest=49.92
        $this->assertEquals(1, $breakdown['schedule'][0]['month']);
        $this->assertEquals(500.00, $breakdown['schedule'][0]['cumulative_principal']);
        $this->assertEquals(12, $breakdown['schedule'][0]['months_invested']);
        $this->assertEquals(4.16, $breakdown['schedule'][0]['monthly_interest_accrued']);
        $this->assertEquals(49.92, $breakdown['schedule'][0]['installment_interest']);
        $this->assertEquals(4.16, $breakdown['schedule'][0]['cumulative_interest']);
        $this->assertEquals(504.16, $breakdown['schedule'][0]['closing_balance']);

        // Month 12: cumulative_principal=6000, cumulative_interest=324.48, closing_balance=6324.48
        $this->assertEquals(12, $breakdown['schedule'][11]['month']);
        $this->assertEquals(6000.00, $breakdown['schedule'][11]['cumulative_principal']);
        $this->assertEquals(1, $breakdown['schedule'][11]['months_invested']);
        $this->assertEquals(49.92, $breakdown['schedule'][11]['monthly_interest_accrued']);
        $this->assertEquals(4.16, $breakdown['schedule'][11]['installment_interest']);
        $this->assertEquals(324.48, $breakdown['schedule'][11]['cumulative_interest']);
        $this->assertEquals(6324.48, $breakdown['schedule'][11]['closing_balance']);

        // Also check without truncation (truncate_monthly_interest = false):
        // (500 * 0.10 / 12) * 78 = 325.00 -> maturity = 6325.00
        $untruncated = Deposit::calculateRdSimpleInterest(500.0, 12, 10.0, false);
        $this->assertEquals(6000.00, $untruncated['total_principal']);
        $this->assertEquals(325.00, $untruncated['total_interest']);
        $this->assertEquals(6325.00, $untruncated['maturity_value']);
    }

    // =======================================================================
    //  3. RD INTEREST & TENURE CALCULATIONS (12, 24, 36, 48 Months & Zero Interest)
    // =======================================================================

    /** @test */
    public function test_calculates_rd_maturity_and_interest_for_12_month_tenure(): void
    {
        // P=500, n=12, r=7% -> monthly_interest = 2.91, sum_of_months = 78
        // Interest = 2.91 * 78 = 226.98, Maturity = 6000 + 226.98 = 6226.98
        $maturity = Deposit::calculateMaturityAmount(500.0, 7.0, 12, 'MONTHS', 'RD', 'MONTHLY');
        $principal = 500.0 * 12;
        $interest = round($maturity - $principal, 2);

        $this->assertEquals(6226.98, $maturity);
        $this->assertEquals(6000.00, $principal);
        $this->assertEquals(226.98, $interest);
    }

    /** @test */
    public function test_calculates_rd_maturity_and_interest_for_24_month_tenure(): void
    {
        // P=1000, n=24, r=8% -> monthly_interest = floor(6.6666... * 100)/100 = 6.66
        // sum_of_months = 24 * 25 / 2 = 300
        // Interest = 6.66 * 300 = 1998.00, Principal = 24000.00, Maturity = 25998.00
        $maturity = Deposit::calculateMaturityAmount(1000.0, 8.0, 24, 'MONTHS', 'RD', 'MONTHLY');
        $principal = 1000.0 * 24;
        $interest = round($maturity - $principal, 2);

        $this->assertEquals(25998.00, $maturity);
        $this->assertEquals(24000.00, $principal);
        $this->assertEquals(1998.00, $interest);
    }

    /** @test */
    public function test_calculates_rd_maturity_and_interest_for_36_month_tenure(): void
    {
        // P=1000, n=36, r=8% -> monthly_interest = 6.66, sum_of_months = 36 * 37 / 2 = 666
        // Interest = 6.66 * 666 = 4435.56, Principal = 36000.00, Maturity = 40435.56
        $maturity = Deposit::calculateMaturityAmount(1000.0, 8.0, 36, 'MONTHS', 'RD', 'MONTHLY');
        $principal = 1000.0 * 36;
        $interest = round($maturity - $principal, 2);

        $this->assertEquals(40435.56, $maturity);
        $this->assertEquals(36000.00, $principal);
        $this->assertEquals(4435.56, $interest);
    }

    /** @test */
    public function test_calculates_rd_maturity_and_interest_for_48_month_tenure(): void
    {
        // P=1000, n=48, r=8% -> monthly_interest = 6.66, sum_of_months = 48 * 49 / 2 = 1176
        // Interest = 6.66 * 1176 = 7832.16, Principal = 48000.00, Maturity = 55832.16
        $maturity = Deposit::calculateMaturityAmount(1000.0, 8.0, 48, 'MONTHS', 'RD', 'MONTHLY');
        $principal = 1000.0 * 48;
        $interest = round($maturity - $principal, 2);

        $this->assertEquals(55832.16, $maturity);
        $this->assertEquals(48000.00, $principal);
        $this->assertEquals(7832.16, $interest);
    }

    /** @test */
    public function test_handles_zero_interest_rate_across_tenures(): void
    {
        // When rate = 0%, Maturity must equal Principal (P * n) and Interest must equal 0.00
        foreach ([12, 24, 36, 48] as $tenure) {
            $maturity = Deposit::calculateMaturityAmount(1000.0, 0.0, $tenure, 'MONTHS', 'RD', 'MONTHLY');
            $principal = 1000.0 * $tenure;
            $interest = round($maturity - $principal, 2);

            $this->assertEquals($principal, $maturity, "Maturity for {$tenure}m at 0% must equal total principal");
            $this->assertEquals(0.00, $interest, "Interest for {$tenure}m at 0% must equal 0.00");
        }
    }

    /** @test */
    public function test_does_not_calculate_rd_as_lump_sum_deposit(): void
    {
        // RD must NOT treat the entire scheduled principal as deposited on day 1.
        // Lump-sum 12-month interest on 6000 at 7% = 420 -> 6420.00
        // RD Simple Interest Product Method gives 6226.98 -- these must differ
        $rdMaturity = Deposit::calculateMaturityAmount(500.0, 7.0, 12, 'MONTHS', 'RD', 'MONTHLY');
        $lumpMaturity = Deposit::calculateMaturityAmount(6000.0, 7.0, 12, 'MONTHS', 'FD', 'ONE_TIME');

        $this->assertNotEquals($lumpMaturity, $rdMaturity, 'RD must not use lump-sum formula');
        $this->assertEquals(6226.98, $rdMaturity);
    }

    // =======================================================================
    //  4. MATURITY AMOUNT CALCULATION
    // =======================================================================

    /** @test */
    public function test_calculates_correct_maturity_amount_for_rd(): void
    {
        // P=500, n=12, r=7% -> maturity = 6226.98
        $maturity = Deposit::calculateMaturityAmount(500.0, 7.0, 12, 'MONTHS', 'RD', 'MONTHLY');
        $this->assertEquals(6226.98, $maturity);
    }

    /** @test */
    public function test_stores_server_calculated_maturity_amount_not_client_supplied(): void
    {
        // Even if a manipulated client sends a different maturity amount,
        // the backend must calculate independently from scheme data.
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload([
                'amount' => 500,
                'maturity_amount' => 99999, // injected fake value
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $deposit = Deposit::where('customer_id', $this->customer->id)
            ->where('deposit_scheme_id', $this->rdScheme->id)
            ->latest()->first();

        $this->assertNotNull($deposit);
        $this->assertEquals(6226.98, (float) $deposit->maturity_amount,
            'Backend must use server-calculated 6226.98, not the injected client value');
    }

    // =======================================================================
    //  5. MATURITY DATE CALCULATION
    // =======================================================================

    /** @test */
    public function test_calculates_maturity_date_as_start_plus_tenure_months(): void
    {
        $start = Carbon::parse('2025-01-01');
        $maturity = Deposit::calculateMaturityDate($start, 12, 'MONTHS');

        $this->assertEquals('2026-01-01', $maturity->toDateString());
    }

    /** @test */
    public function test_calculates_maturity_date_in_years(): void
    {
        $start = Carbon::parse('2025-06-15');
        $maturity = Deposit::calculateMaturityDate($start, 2, 'YEARS');

        $this->assertEquals('2027-06-15', $maturity->toDateString());
    }

    /** @test */
    public function test_calculates_maturity_date_in_days(): void
    {
        $start = Carbon::parse('2025-01-01');
        $maturity = Deposit::calculateMaturityDate($start, 100, 'DAYS');

        $this->assertEquals('2025-04-11', $maturity->toDateString());
    }

    /** @test */
    public function test_stores_correct_maturity_date_on_deposit_creation(): void
    {
        $startDate = now()->toDateString();
        $expected = Carbon::parse($startDate)->addMonths(12)->toDateString();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload([
                'amount' => 500,
                'start_date' => $startDate,
            ]));

        $response->assertRedirect();
        $response->assertSessionHasNoErrors();

        $deposit = Deposit::where('customer_id', $this->customer->id)
            ->where('deposit_scheme_id', $this->rdScheme->id)
            ->latest()->first();

        $this->assertNotNull($deposit);
        $this->assertEquals($expected, $deposit->maturity_date->toDateString());
    }

    // =======================================================================
    //  6. INVALID AND MANIPULATED CLIENT INPUTS
    // =======================================================================

    /** @test */
    public function test_rejects_negative_amount(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => -500]));

        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function test_rejects_missing_amount(): void
    {
        $payload = $this->validRdPayload();
        unset($payload['amount']);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $payload);

        $response->assertSessionHasErrors('amount');
    }

    /** @test */
    public function test_rejects_inactive_scheme(): void
    {
        $inactiveScheme = DepositScheme::create([
            'name' => 'Inactive RD '.uniqid(),
            'code' => 'IRD_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_RD,
            'min_amount' => 500.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 7.00,
            'collection_frequency' => DepositScheme::FREQ_MONTHLY,
            'status' => DepositScheme::STATUS_INACTIVE,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload([
                'amount' => 500,
                'deposit_scheme_id' => $inactiveScheme->id,
            ]));

        $response->assertSessionHasErrors('deposit_scheme_id');
    }

    /** @test */
    public function test_does_not_save_deposit_when_amount_is_below_minimum(): void
    {
        $countBefore = Deposit::where('customer_id', $this->customer->id)->count();

        $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 100]));

        $countAfter = Deposit::where('customer_id', $this->customer->id)->count();

        $this->assertEquals($countBefore, $countAfter,
            'No deposit record must be created when amount is below minimum');
    }

    // =======================================================================
    //  7. EXISTING DEPOSIT WORKFLOW AND PERMISSIONS
    // =======================================================================

    /** @test */
    public function test_requires_authentication_to_create_deposit(): void
    {
        $response = $this->post(route('admin.deposits.store'), $this->validRdPayload(['amount' => 500]));
        $response->assertRedirect();
    }

    /** @test */
    public function test_prevents_cross_branch_deposit_creation(): void
    {
        $otherBranch = Branch::firstOrCreate(
            ['code' => 'OTHER_BR_RD'],
            ['name' => 'Other Branch RD', 'is_active' => true]
        );

        // Customer is in $this->branch, but branch_id is overridden to another branch
        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $this->validRdPayload([
                'amount' => 500,
                'branch_id' => $otherBranch->id,
            ]));

        // Should reject with branch mismatch error
        $response->assertSessionHasErrors('branch_id');
    }

    // =======================================================================
    //  8. ESTIMATED INTEREST ACCESSOR
    // =======================================================================

    /** @test */
    public function test_reports_correct_estimated_interest_via_accessor(): void
    {
        $deposit = new Deposit([
            'amount' => 500,
            'tenure' => 12,
            'tenure_unit' => 'MONTHS',
            'deposit_type' => 'RD',
            'collection_frequency' => 'MONTHLY',
            'maturity_amount' => 6196.29,
            'interest_rate' => 7.00,
        ]);
        $deposit->deposit_type = 'RD';

        // For RD: estimated_interest = maturity - (installment x n)
        // 6196.29 - (500 x 12) = 196.29
        $this->assertEquals(196.29, $deposit->estimated_interest);
    }

    // =======================================================================
    //  9. FRONTEND/BACKEND CONSISTENCY -- create page renders scheme data
    // =======================================================================

    /** @test */
    public function test_create_page_renders_scheme_min_max_in_data_attributes(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.create'));

        $response->assertStatus(200);

        // The scheme select option must carry data-min and data-max attributes
        $response->assertSee('data-min="500', false);   // min_amount attribute
        $response->assertSee('data-max="10000', false); // max_amount attribute
    }

    /** @test */
    public function test_create_page_shows_rd_specific_summary_fields_in_js(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.create'));

        $response->assertStatus(200);
        $response->assertSee('preview_installment');
        $response->assertSee('preview_num_installments');
        $response->assertSee('preview_total_principal');
    }

    /** @test */
    public function test_create_page_shows_amount_validation_hint(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.create'));

        $response->assertStatus(200);
        $response->assertSee('scheme_amount_hint');
    }

    // â”€â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€

    /**
     * Build a valid RD deposit store payload with optional overrides.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    protected function validRdPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'deposit_scheme_id' => $this->rdScheme->id,
            'amount' => 500,
            'start_date' => now()->toDateString(),
            'deposit_date' => now()->toDateString(),
            'payment_mode' => 'CASH',
            'auto_renewal' => 'NONE',
            'payout_frequency' => 'AT_MATURITY',
        ], $overrides);
    }
}
