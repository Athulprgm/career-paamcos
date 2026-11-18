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

class MultiYearDepositInterestRateTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected ?Branch $branch = null;

    protected ?Agent $agent = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'superadmin_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);

        $this->branch = Branch::firstOrCreate(
            ['code' => 'BR-TEST-01'],
            ['name' => 'Main Test Branch', 'status' => 'active']
        );

        $agentRole = Role::firstOrCreate(['slug' => 'agent'], ['name' => 'Agent', 'status' => 'active']);
        $agentUser = User::factory()->create([
            'email' => 'my_agent_'.uniqid().'@test.com',
            'branch_id' => $this->branch->id,
        ]);
        $agentUser->roles()->attach($agentRole->id);

        $rank = AgentRank::first();
        if (! $rank) {
            $rank = AgentRank::create([
                'name' => 'Advisor',
                'level' => 1,
                'min_business' => 0,
            ]);
        }

        $this->agent = Agent::create([
            'user_id' => $agentUser->id,
            'member_code' => 'MYA_'.rand(1000, 9999),
            'agent_id' => 'MYA_'.rand(1000, 9999),
            'name' => 'MY Agent '.uniqid(),
            'email' => $agentUser->email,
            'phone' => '9876543210',
            'branch_id' => $this->branch->id,
            'rank_id' => $rank->id,
            'status' => 'active',
            'joining_date' => now()->toDateString(),
        ]);
    }

    /**
     * Test Super Admin can create a deposit scheme with duration-based interest rates.
     */
    public function test_super_admin_can_create_scheme_with_duration_based_interest_rates(): void
    {
        $code = 'MY_'.strtoupper(substr(uniqid(), -6));
        $payload = [
            'name' => 'Multi-Year Growth Scheme',
            'code' => $code,
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'max_amount' => 500000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_AT_MATURITY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'tenure_rates' => [
                [
                    'tenure' => 1,
                    'tenure_unit' => 'YEARS',
                    'interest_rate' => 10.00,
                    'senior_citizen_extra_rate' => 0.50,
                    'status' => 'ACTIVE',
                ],
                [
                    'tenure' => 2,
                    'tenure_unit' => 'YEARS',
                    'interest_rate' => 10.50,
                    'senior_citizen_extra_rate' => 0.50,
                    'status' => 'ACTIVE',
                ],
                [
                    'tenure' => 4,
                    'tenure_unit' => 'YEARS',
                    'interest_rate' => 11.50,
                    'senior_citizen_extra_rate' => 0.75,
                    'status' => 'ACTIVE',
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), $payload);

        $response->assertRedirect();

        $scheme = DepositScheme::where('code', $code)->first();
        $this->assertNotNull($scheme);
        $this->assertTrue($scheme->hasConfiguredTenureRates());
        $this->assertCount(3, $scheme->interestRates);

        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'senior_citizen_extra_rate' => 0.50,
        ]);

        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.50,
            'senior_citizen_extra_rate' => 0.75,
        ]);
    }

    /**
     * Test updating and syncing duration rates on an existing scheme.
     */
    public function test_super_admin_can_update_and_sync_duration_interest_rates(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Flexi Tenure Scheme',
            'code' => 'FTS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 500,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 8.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            [
                'tenure' => 1,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 9.50,
                'senior_citizen_extra_rate' => 0.50,
            ],
            [
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 10.00,
                'senior_citizen_extra_rate' => 0.50,
            ],
        ], $this->superAdmin->id);

        $this->assertCount(2, $scheme->fresh()->interestRates);

        // Update: remove 1 Year, modify 2 Years to 10.75%, add 5 Years @ 12.00%
        $updatePayload = [
            'name' => $scheme->name,
            'code' => $scheme->code,
            'scheme_type' => $scheme->scheme_type,
            'min_amount' => $scheme->min_amount,
            'tenure' => $scheme->tenure,
            'tenure_unit' => $scheme->tenure_unit,
            'interest_rate' => $scheme->interest_rate,
            'collection_frequency' => $scheme->collection_frequency,
            'status' => DepositScheme::STATUS_ACTIVE,
            'tenure_rates' => [
                [
                    'tenure' => 2,
                    'tenure_unit' => 'YEARS',
                    'interest_rate' => 10.75,
                    'senior_citizen_extra_rate' => 0.60,
                    'status' => 'ACTIVE',
                ],
                [
                    'tenure' => 5,
                    'tenure_unit' => 'YEARS',
                    'interest_rate' => 12.00,
                    'senior_citizen_extra_rate' => 0.75,
                    'status' => 'ACTIVE',
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), $updatePayload);

        $response->assertRedirect();

        $freshScheme = $scheme->fresh();
        $this->assertCount(2, $freshScheme->activeInterestRates);

        // 2 Years tier updated
        $this->assertEquals(10.75, $freshScheme->getInterestRateForTenure(2, 'YEARS'));
        $this->assertEquals(0.60, $freshScheme->getSeniorCitizenExtraRateForTenure(2, 'YEARS'));

        // 5 Years tier added
        $this->assertEquals(12.00, $freshScheme->getInterestRateForTenure(5, 'YEARS'));

        // 1 Year tier removed -> falls back to base scheme rate 8.00
        $this->assertEquals(8.00, $freshScheme->getInterestRateForTenure(1, 'YEARS'));
    }

    /**
     * Test unit normalization and fallback in DepositScheme model.
     */
    public function test_tenure_rate_unit_normalization_and_fallback(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Normalization Test Scheme',
            'code' => 'NTS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            [
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 10.50,
                'senior_citizen_extra_rate' => 0.50,
            ],
            [
                'tenure' => 36,
                'tenure_unit' => 'MONTHS',
                'interest_rate' => 11.00,
                'senior_citizen_extra_rate' => 0.75,
            ],
        ], $this->superAdmin->id);

        // Exact match
        $this->assertEquals(10.50, $scheme->getInterestRateForTenure(2, 'YEARS'));

        // 24 months should normalize to 2 years tier (10.50%)
        $this->assertEquals(10.50, $scheme->getInterestRateForTenure(24, 'MONTHS'));

        // 730 days should normalize to 2 years tier (10.50%)
        $this->assertEquals(10.50, $scheme->getInterestRateForTenure(730, 'DAYS'));

        // 3 years should normalize to 36 months tier (11.00%)
        $this->assertEquals(11.00, $scheme->getInterestRateForTenure(3, 'YEARS'));

        // Unconfigured tenure (e.g. 5 years) falls back to base rate 8.00%
        $this->assertEquals(8.00, $scheme->getInterestRateForTenure(5, 'YEARS'));

        // Applicable interest rate with senior citizen
        $this->assertEquals(11.00, $scheme->getApplicableInterestRate(2, 'YEARS', true));
        $this->assertEquals(10.50, $scheme->getApplicableInterestRate(2, 'YEARS', false));
    }

    /**
     * Test JSON API endpoint rate-for-tenure.
     */
    public function test_api_rate_for_tenure_endpoint(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'API Rate Scheme',
            'code' => 'ARS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.25,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            [
                'tenure' => 4,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 11.50,
                'senior_citizen_extra_rate' => 0.50,
            ],
        ], $this->superAdmin->id);

        // Query configured tier with regular citizen
        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rate-for-tenure', [
                'depositScheme' => $scheme->id,
                'tenure' => 4,
                'tenure_unit' => 'YEARS',
                'is_senior_citizen' => 0,
            ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'interest_rate' => 11.50,
                'applicable_interest_rate' => 11.50,
                'is_tier_rate' => true,
            ]);

        // Query configured tier with senior citizen bonus
        $responseSenior = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rate-for-tenure', [
                'depositScheme' => $scheme->id,
                'tenure' => 4,
                'tenure_unit' => 'YEARS',
                'is_senior_citizen' => 1,
            ]));

        $responseSenior->assertStatus(200)
            ->assertJson([
                'success' => true,
                'interest_rate' => 11.50,
                'senior_citizen_extra_rate' => 0.50,
                'applicable_interest_rate' => 12.00,
                'is_tier_rate' => true,
            ]);

        // Query unconfigured tier -> fallback to 8.25
        $responseFallback = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rate-for-tenure', [
                'depositScheme' => $scheme->id,
                'tenure' => 10,
                'tenure_unit' => 'YEARS',
            ]));

        $responseFallback->assertStatus(200)
            ->assertJson([
                'success' => true,
                'interest_rate' => 8.25,
                'is_tier_rate' => false,
            ]);
    }

    /**
     * Test creating a deposit with multi-year tenure automatically gets the configured rate.
     */
    public function test_creating_deposit_with_tenure_tier_automatically_applies_correct_rate_and_maturity(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Multi-Year Fixed Deposit',
            'code' => 'MYFD_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            [
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 10.50,
                'senior_citizen_extra_rate' => 0.50,
            ],
            [
                'tenure' => 4,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 11.50,
                'senior_citizen_extra_rate' => 0.75,
            ],
        ], $this->superAdmin->id);

        $customer = Customer::create([
            'customer_number' => 'CUST-'.uniqid(),
            'name' => 'Regular Investor',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '9876543210',
            'date_of_birth' => '1990-05-15', // Age ~36, not senior
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $startDate = Carbon::today()->toDateString();
        $principal = 100000.00;

        // Submit deposit for 4 YEARS
        $payload = [
            'customer_id' => $customer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 4,
            'tenure_unit' => 'YEARS',
            'amount' => $principal,
            'start_date' => $startDate,
            'status_action' => 'active',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $payload);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $customer->id)->latest('id')->first();
        $this->assertNotNull($deposit);

        // Rate should be 11.50% (from 4-year tier, not base 8.00%)
        $this->assertEquals(11.50, (float) $deposit->interest_rate);
        $this->assertEquals(4, (int) $deposit->tenure);
        $this->assertEquals('YEARS', $deposit->tenure_unit);

        // Expected maturity for 4 years @ 11.50% using annual compounding: 100,000 * (1 + 0.115)^4 = 154,560.84
        $expectedMaturity = Deposit::calculateMaturityAmount(100000, 11.50, 4, 'YEARS', 'FD', 'ONE_TIME');
        $this->assertEqualsWithDelta($expectedMaturity, (float) $deposit->maturity_amount, 0.01);
    }

    /**
     * Test creating a deposit for a senior citizen applies the senior citizen tier bonus.
     */
    public function test_creating_deposit_for_senior_citizen_applies_senior_tier_bonus(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'Senior Growth Scheme',
            'code' => 'SGS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 8.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            [
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 10.50,
                'senior_citizen_extra_rate' => 0.50,
            ],
        ], $this->superAdmin->id);

        // Senior Citizen customer (DOB in 1955 -> age 70+)
        $seniorCustomer = Customer::create([
            'customer_number' => 'CUST-'.uniqid(),
            'name' => 'Senior Citizen Customer',
            'customer_type' => 'INDIVIDUAL',
            'mobile' => '9812345678',
            'date_of_birth' => '1955-01-01',
            'branch_id' => $this->branch->id,
            'agent_id' => $this->agent->id,
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $payload = [
            'customer_id' => $seniorCustomer->id,
            'agent_id' => $this->agent->id,
            'branch_id' => $this->branch->id,
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'amount' => 50000.00,
            'start_date' => Carbon::today()->toDateString(),
            'status_action' => 'active',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposits.store'), $payload);

        $response->assertRedirect();

        $deposit = Deposit::where('customer_id', $seniorCustomer->id)->latest('id')->first();
        $this->assertNotNull($deposit);

        // Rate should be 10.50 + 0.50 = 11.00%
        $this->assertEquals(11.00, (float) $deposit->interest_rate);

        // Expected maturity for 2 years @ 11.00% using annual compounding: 50,000 * (1 + 0.11)^2 = 61,605.00
        $expectedMaturity = Deposit::calculateMaturityAmount(50000, 11.00, 2, 'YEARS', 'FD', 'ONE_TIME');
        $this->assertEqualsWithDelta($expectedMaturity, (float) $deposit->maturity_amount, 0.01);
    }

    /**
     * Test scheme show and create views render the duration interest rate tier configuration.
     */
    public function test_scheme_show_and_deposit_create_views_render_tenure_configuration(): void
    {
        $scheme = DepositScheme::create([
            'name' => 'View Render Scheme',
            'code' => 'VRS_'.uniqid(),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 1000,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncInterestRates([
            [
                'tenure' => 3,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 11.25,
                'senior_citizen_extra_rate' => 0.50,
            ],
        ], $this->superAdmin->id);

        // 1. Show page displays Standard Interest Rate
        $showResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.show', $scheme));

        $showResponse->assertStatus(200);
        $showResponse->assertSee('9.00% p.a.');

        // 2. Superadmin interest rates route now redirects to deposit-schemes index
        $ratesResponse = $this->actingAs($this->superAdmin)
            ->get(route('superadmin.interest-rates.index', ['deposit_scheme_id' => $scheme->id]));

        $ratesResponse->assertRedirect(route('admin.deposit-schemes.index'));

        // 3. Deposit create page displays tenure selector and summary fields
        $createDepositResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposits.create'));

        $createDepositResponse->assertStatus(200);
        $createDepositResponse->assertSee('tenure_tier_select');
        $createDepositResponse->assertSee('is_senior_citizen');
        $createDepositResponse->assertSee('preview_maturity_amount');
    }

    /**
     * Test bidirectional synchronization between deposit-schemes and superadmin/interest-rates.
     */
    public function test_deposit_schemes_and_superadmin_interest_rates_data_sync_bidirectionally(): void
    {
        // 1. Create a scheme via admin deposit-schemes
        $code = 'SYNC_'.strtoupper(substr(uniqid(), -6));
        $createResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Auto Synced Scheme',
                'code' => $code,
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 5000,
                'tenure' => 2,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 10.50,
                'senior_citizen_extra_rate' => 0.50,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $scheme = DepositScheme::where('code', $code)->firstOrFail();
        $createResponse->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        // Assert record in deposit_scheme_interest_rates was automatically created
        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 10.50,
            'senior_citizen_extra_rate' => 0.50,
            'status' => 'ACTIVE',
        ]);

        // 2. Update scheme in admin deposit-schemes
        $updateResponse = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), [
                'name' => 'Auto Synced Scheme Updated',
                'code' => $code,
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 5000,
                'tenure' => 2,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 11.00,
                'senior_citizen_extra_rate' => 0.75,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $updateResponse->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        // Assert interest rate record was synchronized
        $this->assertDatabaseHas('deposit_scheme_interest_rates', [
            'deposit_scheme_id' => $scheme->id,
            'tenure' => 2,
            'tenure_unit' => 'YEARS',
            'interest_rate' => 11.00,
            'senior_citizen_extra_rate' => 0.75,
        ]);

        // 3. Update interest rate from superadmin/interest-rates
        $rateRecord = $scheme->interestRates()->firstOrFail();
        $rateUpdateResponse = $this->actingAs($this->superAdmin)
            ->put(route('superadmin.interest-rates.update', $rateRecord), [
                'deposit_scheme_id' => $scheme->id,
                'tenure' => 2,
                'tenure_unit' => 'YEARS',
                'interest_rate' => 12.00,
                'senior_citizen_extra_rate' => 1.00,
                'effective_from' => '2026-01-01',
                'status' => 'ACTIVE',
            ]);

        $rateUpdateResponse->assertRedirect();

        // Assert deposit scheme rate was synchronized
        $this->assertEquals(12.00, (float) $scheme->fresh()->interest_rate);
        $this->assertEquals(1.00, (float) $scheme->fresh()->senior_citizen_extra_rate);

        // 4. Test Sync All button/route
        $syncResponse = $this->actingAs($this->superAdmin)
            ->post(route('superadmin.interest-rates.sync'));

        $syncResponse->assertSessionHas('success');
    }
}
