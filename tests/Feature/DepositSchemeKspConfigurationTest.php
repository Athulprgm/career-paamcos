<?php

namespace Tests\Feature;

use App\Models\DepositScheme;
use App\Models\Role;
use App\Models\User;
use DOMDocument;
use DOMXPath;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositSchemeKspConfigurationTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin KSP Test',
            'email' => 'ksp_superadmin_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);
    }

    public function test_create_view_renders_exactly_eight_ksp_fields_with_default_values_hidden_by_default(): void
    {
        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.create'));

        $response->assertSee('KSP SPECIAL CONFIGURATION');
        $response->assertSee('id="ksp_configuration_card"', false);
        $response->assertSee('data-ksp-active="false"', false);

        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new DOMXPath($dom);

        $fields = $xpath->query('//*[@id="ksp_configuration_card"]//input | //*[@id="ksp_configuration_card"]//select');
        $this->assertSame(8, $fields->length);

        $this->assertSame('15000', $xpath->query('//*[@id="ksp_annual_deposit_amount"]')->item(0)?->getAttribute('value'));
        $this->assertSame('10', $xpath->query('//*[@id="ksp_deposit_period"]')->item(0)?->getAttribute('value'));
        $this->assertSame('300000', $xpath->query('//*[@id="ksp_insurance_coverage"]')->item(0)?->getAttribute('value'));
        $this->assertSame('11', $xpath->query('//*[@id="ksp_return_start_year"]')->item(0)?->getAttribute('value'));
        $this->assertSame('20', $xpath->query('//*[@id="ksp_return_end_year"]')->item(0)?->getAttribute('value'));
        $this->assertSame('45000', $xpath->query('//*[@id="ksp_annual_return_amount"]')->item(0)?->getAttribute('value'));
        $this->assertSame('Yearly', $xpath->query('//*[@id="ksp_return_frequency"]/option[@selected]')->item(0)?->getAttribute('value'));
        $this->assertSame('Not Allowed', $xpath->query('//*[@id="ksp_premature_withdrawal"]/option[@selected]')->item(0)?->getAttribute('value'));

        $response->assertSee('id="sec2_max_amount_col"', false);
        $response->assertSee('id="sec2_interest_rate_col"', false);
        $response->assertSee('id="sec2_senior_rate_col"', false);
        $response->assertSee('id="sec2_payout_freq_col"', false);
        $response->assertSee('id="sec2_min_amount_col"', false);
        $response->assertSee('id="sec2_tenure_col"', false);
        $response->assertSee('id="sec2_tenure_unit_col"', false);
        $response->assertSee('id="sec2_collection_freq_col"', false);
        $response->assertSee('syncKspToBaseFields', false);
    }

    public function test_store_creates_ksp_scheme_auto_syncing_base_fields_when_hidden_section_two_fields_are_omitted(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Kissan Samrudhi Plan (KSP)',
                'code' => 'KSP',
                'scheme_type' => DepositScheme::TYPE_FD,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ksp_annual_deposit_amount' => 20000,
                'ksp_deposit_period' => 10,
                'ksp_insurance_coverage' => 300000,
                'ksp_return_start_year' => 11,
                'ksp_return_end_year' => 20,
                'ksp_annual_return_amount' => 60000,
                'ksp_return_frequency' => 'Yearly',
                'ksp_premature_withdrawal' => 'Not Allowed',
            ]);

        $scheme = DepositScheme::where('code', 'KSP')->firstOrFail();
        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $this->assertSame(20000.0, (float) $scheme->min_amount);
        $this->assertSame(10, (int) $scheme->tenure);
        $this->assertSame('YEARS', $scheme->tenure_unit);
        $this->assertSame(0.0, (float) $scheme->interest_rate);
    }

    public function test_store_creates_ksp_scheme_and_persists_all_eight_ksp_fields(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $payload = [
            'name' => 'Kissan Samrudhi Plan (KSP)',
            'code' => 'KSP',
            'scheme_type' => DepositScheme::TYPE_FD,
            'description' => 'Long-term Kissan Samrudhi Plan with guaranteed annual returns and insurance coverage.',
            'min_amount' => 15000.00,
            'max_amount' => 500000.00,
            'tenure' => 20,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'interest_payout_frequency' => DepositScheme::PAYOUT_ANNUALLY,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ksp_annual_deposit_amount' => 15000,
            'ksp_deposit_period' => 10,
            'ksp_insurance_coverage' => 300000,
            'ksp_return_start_year' => 11,
            'ksp_return_end_year' => 20,
            'ksp_annual_return_amount' => 45000,
            'ksp_return_frequency' => 'Yearly',
            'ksp_premature_withdrawal' => 'Not Allowed',
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), $payload);

        $scheme = DepositScheme::where('code', 'KSP')->firstOrFail();
        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $this->assertTrue($scheme->isKsp());
        $this->assertSame([
            'annual_deposit_amount' => 15000.0,
            'deposit_period' => 10,
            'insurance_coverage' => 300000.0,
            'return_start_year' => 11,
            'return_end_year' => 20,
            'annual_return_amount' => 45000.0,
            'return_frequency' => 'Yearly',
            'premature_withdrawal' => 'Not Allowed',
        ], $scheme->getKspConfig());
    }

    public function test_edit_view_for_ksp_scheme_shows_ksp_section_and_populates_saved_values(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $scheme = DepositScheme::create([
            'name' => 'Kissan Samrudhi Plan',
            'code' => 'KSP',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 20000.00,
            'tenure' => 22,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.50,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ksp_configuration' => [
                'annual_deposit_amount' => 25000.00,
                'deposit_period' => 12,
                'insurance_coverage' => 500000.00,
                'return_start_year' => 13,
                'return_end_year' => 22,
                'annual_return_amount' => 75000.00,
                'return_frequency' => 'Half-Yearly',
                'premature_withdrawal' => 'Allowed with Penalty',
            ],
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.edit', $scheme));

        $response->assertSee('KSP SPECIAL CONFIGURATION');
        $response->assertSee('data-ksp-active="true"', false);

        $dom = new DOMDocument;
        @$dom->loadHTML($response->getContent());
        $xpath = new DOMXPath($dom);

        $this->assertSame('25000', $xpath->query('//*[@id="ksp_annual_deposit_amount"]')->item(0)?->getAttribute('value'));
        $this->assertSame('12', $xpath->query('//*[@id="ksp_deposit_period"]')->item(0)?->getAttribute('value'));
        $this->assertSame('500000', $xpath->query('//*[@id="ksp_insurance_coverage"]')->item(0)?->getAttribute('value'));
        $this->assertSame('13', $xpath->query('//*[@id="ksp_return_start_year"]')->item(0)?->getAttribute('value'));
        $this->assertSame('22', $xpath->query('//*[@id="ksp_return_end_year"]')->item(0)?->getAttribute('value'));
        $this->assertSame('75000', $xpath->query('//*[@id="ksp_annual_return_amount"]')->item(0)?->getAttribute('value'));
        $this->assertSame('Half-Yearly', $xpath->query('//*[@id="ksp_return_frequency"]/option[@selected]')->item(0)?->getAttribute('value'));
        $this->assertSame('Allowed with Penalty', $xpath->query('//*[@id="ksp_premature_withdrawal"]/option[@selected]')->item(0)?->getAttribute('value'));
    }

    public function test_update_persists_modified_ksp_configuration_values(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $scheme = DepositScheme::create([
            'name' => 'Kissan Samrudhi Plan',
            'code' => 'KSP',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 15000.00,
            'tenure' => 20,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ksp_configuration' => DepositScheme::KSP_DEFAULT_CONFIG,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), [
                'name' => 'Kissan Samrudhi Plan Enhanced',
                'code' => 'KSP',
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 30000.00,
                'tenure' => 25,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 9.25,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ksp_annual_deposit_amount' => 30000,
                'ksp_deposit_period' => 12,
                'ksp_insurance_coverage' => 600000,
                'ksp_return_start_year' => 13,
                'ksp_return_end_year' => 25,
                'ksp_annual_return_amount' => 90000,
                'ksp_return_frequency' => 'Quarterly',
                'ksp_premature_withdrawal' => 'Allowed',
            ]);

        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $scheme->refresh();
        $this->assertSame('Kissan Samrudhi Plan Enhanced', $scheme->name);
        $this->assertSame([
            'annual_deposit_amount' => 30000.0,
            'deposit_period' => 12,
            'insurance_coverage' => 600000.0,
            'return_start_year' => 13,
            'return_end_year' => 25,
            'annual_return_amount' => 90000.0,
            'return_frequency' => 'Quarterly',
            'premature_withdrawal' => 'Allowed',
        ], $scheme->getKspConfig());
    }

    public function test_normal_scheme_creation_and_editing_hides_ksp_and_never_overwrites_ksp_record(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $kspScheme = DepositScheme::create([
            'name' => 'Kissan Samrudhi Plan',
            'code' => 'KSP',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 15000.00,
            'tenure' => 20,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ksp_configuration' => DepositScheme::KSP_DEFAULT_CONFIG,
        ]);

        $normalCode = 'NORM'.rand(1000, 9999);
        $createResponse = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Normal Fixed Deposit',
                'code' => $normalCode,
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 1000.00,
                'tenure' => 12,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 8.00,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'status' => DepositScheme::STATUS_ACTIVE,
            ]);

        $normalScheme = DepositScheme::where('code', $normalCode)->firstOrFail();
        $createResponse->assertRedirect(route('admin.deposit-schemes.show', $normalScheme));
        $this->assertFalse($normalScheme->isKsp());
        $this->assertNull($normalScheme->ksp_configuration);

        $editNormalResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.edit', $normalScheme));
        $editNormalResponse->assertSee('data-ksp-active="false"', false);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $normalScheme), [
                'name' => 'Normal Fixed Deposit Updated',
                'code' => $normalCode,
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 2000.00,
                'tenure' => 24,
                'tenure_unit' => DepositScheme::TENURE_MONTHS,
                'interest_rate' => 8.50,
                'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
                'status' => DepositScheme::STATUS_ACTIVE,
            ])->assertRedirect(route('admin.deposit-schemes.show', $normalScheme));

        $kspScheme->refresh();
        $this->assertSame(15000.0, $kspScheme->getKspConfig()['annual_deposit_amount']);
        $this->assertSame(300000.0, $kspScheme->getKspConfig()['insurance_coverage']);
    }

    public function test_ksp_validation_rejects_annual_deposit_below_minimum_fifteen_thousand(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $response = $this->actingAs($this->superAdmin)
            ->from(route('admin.deposit-schemes.create'))
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Kissan Samrudhi Plan',
                'code' => 'KSP',
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 15000.00,
                'tenure' => 20,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 9.00,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ksp_annual_deposit_amount' => 12000,
                'ksp_deposit_period' => 10,
                'ksp_insurance_coverage' => 300000,
                'ksp_return_start_year' => 11,
                'ksp_return_end_year' => 20,
                'ksp_annual_return_amount' => 45000,
                'ksp_return_frequency' => 'Yearly',
                'ksp_premature_withdrawal' => 'Not Allowed',
            ]);

        $response->assertRedirect(route('admin.deposit-schemes.create'));
        $response->assertSessionHasErrors([
            'ksp_annual_deposit_amount' => 'Annual Deposit Amount must be at least ₹15,000.',
        ]);
        $this->assertDatabaseMissing('deposit_schemes', ['code' => 'KSP']);
    }

    public function test_ksp_validation_rejects_return_end_year_not_greater_than_return_start_year(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $response = $this->actingAs($this->superAdmin)
            ->from(route('admin.deposit-schemes.create'))
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Kissan Samrudhi Plan',
                'code' => 'KSP',
                'scheme_type' => DepositScheme::TYPE_FD,
                'min_amount' => 15000.00,
                'tenure' => 20,
                'tenure_unit' => DepositScheme::TENURE_YEARS,
                'interest_rate' => 9.00,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ksp_annual_deposit_amount' => 15000,
                'ksp_deposit_period' => 10,
                'ksp_insurance_coverage' => 300000,
                'ksp_return_start_year' => 15,
                'ksp_return_end_year' => 15,
                'ksp_annual_return_amount' => 45000,
                'ksp_return_frequency' => 'Yearly',
                'ksp_premature_withdrawal' => 'Not Allowed',
            ]);

        $response->assertRedirect(route('admin.deposit-schemes.create'));
        $response->assertSessionHasErrors([
            'ksp_return_end_year' => 'Return End Year must be greater than Return Start Year.',
        ]);
        $this->assertDatabaseMissing('deposit_schemes', ['code' => 'KSP']);
    }

    public function test_get_rates_endpoint_includes_ksp_metadata_for_ksp_scheme(): void
    {
        DepositScheme::withTrashed()->where('code', 'KSP')->forceDelete();

        $kspScheme = DepositScheme::create([
            'name' => 'Kissan Samrudhi Plan',
            'code' => 'KSP',
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 15000.00,
            'tenure' => 20,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 9.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            'ksp_configuration' => DepositScheme::KSP_DEFAULT_CONFIG,
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->getJson(route('admin.deposit-schemes.rates', $kspScheme));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'scheme_code' => 'KSP',
                'is_ksp' => true,
                'ksp_configuration' => [
                    'annual_deposit_amount' => 15000,
                    'deposit_period' => 10,
                    'insurance_coverage' => 300000,
                    'return_start_year' => 11,
                    'return_end_year' => 20,
                    'annual_return_amount' => 45000,
                    'return_frequency' => 'Yearly',
                    'premature_withdrawal' => 'Not Allowed',
                ],
            ]);
    }

    public function test_year_wise_ksp_plans_configure_exact_return_years_for_one_two_and_ten_year_deposits(): void
    {
        DepositScheme::withTrashed()->whereIn('code', ['KSP1Y', 'KSP2Y'])->forceDelete();

        // 1. KSP 1 Year -> Return received in 11th Year (Start: 11, End: 11)
        $resp1Y = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Kissan Samrudhi Plan (KSP) 1 Year',
                'code' => 'KSP1Y',
                'scheme_type' => DepositScheme::TYPE_FD,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ksp_annual_deposit_amount' => 15000,
                'ksp_deposit_period' => 1,
                'ksp_insurance_coverage' => 300000,
                'ksp_return_start_year' => 11,
                'ksp_return_end_year' => 11,
                'ksp_annual_return_amount' => 45000,
                'ksp_return_frequency' => 'Yearly',
                'ksp_premature_withdrawal' => 'Not Allowed',
            ]);

        $ksp1Y = DepositScheme::where('code', 'KSP1Y')->firstOrFail();
        $resp1Y->assertRedirect(route('admin.deposit-schemes.show', $ksp1Y));
        $this->assertTrue($ksp1Y->isKsp());
        $this->assertSame(1, $ksp1Y->getKspConfig()['deposit_period']);
        $this->assertSame(11, $ksp1Y->getKspConfig()['return_start_year']);
        $this->assertSame(11, $ksp1Y->getKspConfig()['return_end_year']);

        // 2. KSP 2 Year -> Return received in 11th and 12th Year (Start: 11, End: 12)
        $resp2Y = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), [
                'name' => 'Kissan Samrudhi Plan (KSP) 2 Year',
                'code' => 'KSP2Y',
                'scheme_type' => DepositScheme::TYPE_FD,
                'status' => DepositScheme::STATUS_ACTIVE,
                'ksp_annual_deposit_amount' => 15000,
                'ksp_deposit_period' => 2,
                'ksp_insurance_coverage' => 300000,
                'ksp_annual_return_amount' => 45000,
                'ksp_return_frequency' => 'Yearly',
                'ksp_premature_withdrawal' => 'Not Allowed',
            ]);

        $ksp2Y = DepositScheme::where('code', 'KSP2Y')->firstOrFail();
        $resp2Y->assertRedirect(route('admin.deposit-schemes.show', $ksp2Y));
        $this->assertTrue($ksp2Y->isKsp());
        $this->assertSame(2, $ksp2Y->getKspConfig()['deposit_period']);
        $this->assertSame(11, $ksp2Y->getKspConfig()['return_start_year']);
        $this->assertSame(12, $ksp2Y->getKspConfig()['return_end_year']);

        // 3. Verify model helper for all 1..10 year tiers
        for ($yr = 1; $yr <= 10; $yr++) {
            $cfg = DepositScheme::getKspConfigForPeriod($yr);
            $this->assertSame($yr, $cfg['deposit_period']);
            $this->assertSame(11, $cfg['return_start_year']);
            $this->assertSame(10 + $yr, $cfg['return_end_year']);
        }
    }
}
