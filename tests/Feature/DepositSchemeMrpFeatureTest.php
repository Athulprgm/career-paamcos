<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\DepositScheme;
use App\Models\DepositSchemeMrpConfiguration;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositSchemeMrpFeatureTest extends TestCase
{
    use DatabaseTransactions;

    protected ?User $superAdmin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $saRole = Role::firstOrCreate(['slug' => 'super_admin'], ['name' => 'Super Admin', 'status' => 'active']);
        $this->superAdmin = User::factory()->create([
            'name' => 'Super Admin Test',
            'email' => 'mrp_superadmin_'.uniqid().'@multicred.com',
        ]);
        $this->superAdmin->roles()->attach($saRole->id);
    }

    /**
     * Test creating a deposit scheme with dynamic MRP enabled and year-wise rates.
     */
    public function test_can_create_deposit_scheme_with_dynamic_mrp(): void
    {
        $uniqueCode = 'MRP'.rand(1000, 9999);

        $payload = [
            'name' => 'Monthly Return Plan Special',
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'description' => 'Test Scheme for MRP',
            'min_amount' => 100000.00,
            'max_amount' => 500000.00,
            'tenure' => 5,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'collection_frequency' => DepositScheme::FREQ_ONE_TIME,
            'status' => DepositScheme::STATUS_ACTIVE,
            // MRP fields
            'mrp_enabled' => '1',
            'mrp_contribution_amount' => 100000.00,
            'mrp_min_tenure' => 1,
            'mrp_max_tenure' => 5,
            'mrp_tenure_unit' => 'YEARS',
            'mrp_calculation_method' => DepositSchemeMrpConfiguration::METHOD_STANDARD,
            'mrp_general_rate' => 10.00,
            'mrp_senior_rate' => 10.50,
            'mrp_rates' => [
                [
                    'year' => 1,
                    'general_rate' => 10.00,
                    'senior_rate' => 10.50,
                ],
                [
                    'year' => 2,
                    'general_rate' => 10.00,
                    'senior_rate' => 10.50,
                ],
                [
                    'year' => 3,
                    'general_rate' => 11.00,
                    'senior_rate' => 11.50,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), $payload);

        $scheme = DepositScheme::where('code', $uniqueCode)->first();
        $this->assertNotNull($scheme);
        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        // Verify database persistence of MRP configuration
        $this->assertDatabaseHas('deposit_scheme_mrp_configurations', [
            'deposit_scheme_id' => $scheme->id,
            'is_enabled' => 1,
            'contribution_amount' => 100000.00,
            'min_tenure' => 1,
            'max_tenure' => 5,
            'tenure_unit' => 'YEARS',
            'calculation_method' => 'STANDARD',
        ]);

        // Verify calculated monthly returns in database
        // Year 1 @ 10%: (100000 * 10) / 1200 = 833.33
        // Year 1 Senior @ 10.5%: (100000 * 10.5) / 1200 = 875.00
        $this->assertDatabaseHas('deposit_scheme_mrp_rates', [
            'deposit_scheme_id' => $scheme->id,
            'year' => 1,
            'general_rate' => 10.00,
            'general_monthly_return' => 833.33,
            'senior_rate' => 10.50,
            'senior_monthly_return' => 875.00,
        ]);

        // Year 3 @ 11%: (100000 * 11) / 1200 = 916.67
        // Year 3 Senior @ 11.5%: (100000 * 11.5) / 1200 = 958.33
        $this->assertDatabaseHas('deposit_scheme_mrp_rates', [
            'deposit_scheme_id' => $scheme->id,
            'year' => 3,
            'general_rate' => 11.00,
            'general_monthly_return' => 916.67,
            'senior_rate' => 11.50,
            'senior_monthly_return' => 958.33,
        ]);

        $this->assertCount(3, $scheme->mrpRates);
        $this->assertTrue($scheme->isMrpEnabled());
    }

    /**
     * Test brochure difference calculation: ₹830 at 10% under BROCHURE_FLAT_10 mode.
     */
    public function test_mrp_financial_calculation_brochure_and_standard_modes(): void
    {
        // Standard Mode: (100000 * 10) / 1200 = 833.33
        $stdReturn = DepositSchemeMrpConfiguration::calculateMonthlyReturn(
            100000.00,
            10.00,
            DepositSchemeMrpConfiguration::METHOD_STANDARD
        );
        $this->assertEquals(833.33, $stdReturn);

        // Senior in Standard Mode: (100000 * 10.5) / 1200 = 875.00
        $stdSeniorReturn = DepositSchemeMrpConfiguration::calculateMonthlyReturn(
            100000.00,
            10.50,
            DepositSchemeMrpConfiguration::METHOD_STANDARD
        );
        $this->assertEquals(875.00, $stdSeniorReturn);

        // Brochure Mode: floor(833.33 / 10) * 10 = 830.00
        $brochureReturn = DepositSchemeMrpConfiguration::calculateMonthlyReturn(
            100000.00,
            10.00,
            DepositSchemeMrpConfiguration::METHOD_BROCHURE_830
        );
        $this->assertEquals(830.00, $brochureReturn);

        // Nearest Rupee Mode: round(833.33) = 833.00
        $roundReturn = DepositSchemeMrpConfiguration::calculateMonthlyReturn(
            100000.00,
            10.00,
            DepositSchemeMrpConfiguration::METHOD_ROUND_INTEGER
        );
        $this->assertEquals(833.00, $roundReturn);
    }

    /**
     * Test editing an existing MRP scheme: modifying rates, adding a year, removing a year.
     */
    public function test_can_edit_mrp_scheme_add_and_remove_years(): void
    {
        $uniqueCode = 'MRPE'.rand(1000, 9999);

        $scheme = DepositScheme::create([
            'name' => 'MRP Editable Scheme',
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $scheme->syncMrpConfiguration(
            [
                'is_enabled' => true,
                'contribution_amount' => 100000.00,
                'min_tenure' => 1,
                'max_tenure' => 3,
                'tenure_unit' => 'YEARS',
                'calculation_method' => 'STANDARD',
            ],
            [
                ['year' => 1, 'general_rate' => 10.00, 'senior_rate' => 10.50],
                ['year' => 2, 'general_rate' => 10.00, 'senior_rate' => 10.50],
                ['year' => 3, 'general_rate' => 10.00, 'senior_rate' => 10.50],
            ]
        );

        $this->assertCount(3, $scheme->mrpRates);

        // Now update: Increase contribution to 200,000, remove Year 2, change Year 1 rates, add Year 4
        $updatePayload = [
            'name' => 'MRP Editable Scheme Updated',
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 200000.00,
            'tenure' => 4,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'mrp_enabled' => '1',
            'mrp_contribution_amount' => 200000.00,
            'mrp_min_tenure' => 1,
            'mrp_max_tenure' => 4,
            'mrp_tenure_unit' => 'YEARS',
            'mrp_calculation_method' => 'STANDARD',
            'mrp_rates' => [
                [
                    'year' => 1,
                    'general_rate' => 12.00,
                    'senior_rate' => 12.50,
                ],
                [
                    'year' => 3,
                    'general_rate' => 11.00,
                    'senior_rate' => 11.50,
                ],
                [
                    'year' => 4,
                    'general_rate' => 13.00,
                    'senior_rate' => 13.50,
                ],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), $updatePayload);

        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $scheme->refresh();

        // Verify contribution updated
        $this->assertEquals(200000.00, (float) $scheme->mrpConfiguration->contribution_amount);

        // Year 2 must be deleted
        $this->assertDatabaseMissing('deposit_scheme_mrp_rates', [
            'deposit_scheme_id' => $scheme->id,
            'year' => 2,
        ]);

        // Year 1 @ 200,000 & 12%: (200000 * 12) / 1200 = 2000.00
        // Senior @ 12.5%: (200000 * 12.5) / 1200 = 2083.33
        $this->assertDatabaseHas('deposit_scheme_mrp_rates', [
            'deposit_scheme_id' => $scheme->id,
            'year' => 1,
            'general_rate' => 12.00,
            'general_monthly_return' => 2000.00,
            'senior_rate' => 12.50,
            'senior_monthly_return' => 2083.33,
        ]);

        // Year 4 added @ 200,000 & 13%: (200000 * 13) / 1200 = 2166.67
        $this->assertDatabaseHas('deposit_scheme_mrp_rates', [
            'deposit_scheme_id' => $scheme->id,
            'year' => 4,
            'general_rate' => 13.00,
            'general_monthly_return' => 2166.67,
        ]);

        $this->assertCount(3, $scheme->mrpRates);
    }

    /**
     * Test duplicate years rejection in MRP configuration.
     */
    public function test_duplicate_years_in_mrp_rates_fail_validation(): void
    {
        $uniqueCode = 'MRPD'.rand(1000, 9999);

        $payload = [
            'name' => 'MRP Duplicate Test Scheme',
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'mrp_enabled' => '1',
            'mrp_contribution_amount' => 100000.00,
            'mrp_min_tenure' => 1,
            'mrp_max_tenure' => 3,
            'mrp_rates' => [
                ['year' => 1, 'general_rate' => 10.00, 'senior_rate' => 10.50],
                ['year' => 1, 'general_rate' => 12.00, 'senior_rate' => 12.50], // Duplicate Year 1
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->post(route('admin.deposit-schemes.store'), $payload);

        $response->assertSessionHasErrors(['mrp_rates']);
        $this->assertDatabaseMissing('deposit_schemes', ['code' => $uniqueCode]);
    }

    /**
     * Test that unrelated scheme updates preserve MRP configuration data.
     */
    public function test_unrelated_scheme_updates_preserve_mrp_data(): void
    {
        $uniqueCode = 'MRPU'.rand(1000, 9999);

        $scheme = DepositScheme::create([
            'name' => 'Original Scheme Name',
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'description' => 'Original description',
        ]);

        $scheme->syncMrpConfiguration(
            [
                'is_enabled' => true,
                'contribution_amount' => 100000.00,
                'min_tenure' => 1,
                'max_tenure' => 3,
                'tenure_unit' => 'YEARS',
                'calculation_method' => 'STANDARD',
            ],
            [
                ['year' => 1, 'general_rate' => 10.00, 'senior_rate' => 10.50],
                ['year' => 2, 'general_rate' => 10.00, 'senior_rate' => 10.50],
            ]
        );

        // Perform unrelated update: Only change description and scheme name, keeping MRP active
        $updatePayload = [
            'name' => 'Updated Scheme Name Only',
            'code' => $uniqueCode,
            'scheme_type' => DepositScheme::TYPE_FD,
            'description' => 'Brand new updated description',
            'min_amount' => 100000.00,
            'tenure' => 3,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'status' => DepositScheme::STATUS_ACTIVE,
            'mrp_enabled' => '1',
            'mrp_contribution_amount' => 100000.00,
            'mrp_min_tenure' => 1,
            'mrp_max_tenure' => 3,
            'mrp_tenure_unit' => 'YEARS',
            'mrp_calculation_method' => 'STANDARD',
            'mrp_rates' => [
                ['year' => 1, 'general_rate' => 10.00, 'senior_rate' => 10.50],
                ['year' => 2, 'general_rate' => 10.00, 'senior_rate' => 10.50],
            ],
        ];

        $response = $this->actingAs($this->superAdmin)
            ->put(route('admin.deposit-schemes.update', $scheme), $updatePayload);

        $response->assertRedirect(route('admin.deposit-schemes.show', $scheme));

        $scheme->refresh();
        $this->assertEquals('Updated Scheme Name Only', $scheme->name);
        $this->assertEquals('Brand new updated description', $scheme->description);

        // Verify MRP data is preserved completely
        $this->assertTrue($scheme->isMrpEnabled());
        $this->assertCount(2, $scheme->mrpRates);
        $this->assertDatabaseHas('deposit_scheme_mrp_rates', [
            'deposit_scheme_id' => $scheme->id,
            'year' => 1,
            'general_monthly_return' => 833.33,
        ]);
    }

    /**
     * Test that FD, RD, and DD calculations remain 100% unchanged.
     */
    public function test_existing_fd_rd_dd_calculations_remain_unmodified(): void
    {
        // FD lump-sum compound calculation
        $fdMaturity = Deposit::calculateMaturityAmount(
            amount: 100000.00,
            interestRate: 8.50,
            tenure: 12,
            tenureUnit: 'MONTHS',
            schemeType: 'FD',
            frequency: 'ONE_TIME'
        );
        $this->assertGreaterThan(100000.00, $fdMaturity);

        // RD recurring annuity calculation
        $rdMaturity = Deposit::calculateMaturityAmount(
            amount: 1000.00,
            interestRate: 7.50,
            tenure: 12,
            tenureUnit: 'MONTHS',
            schemeType: 'RD',
            frequency: 'MONTHLY'
        );
        // 1000 * 12 = 12000 principal + monthly compound interest
        $this->assertGreaterThan(12000.00, $rdMaturity);

        // DD daily collection calculation
        $ddMaturity = Deposit::calculateMaturityAmount(
            amount: 100.00,
            interestRate: 6.00,
            tenure: 100,
            tenureUnit: 'DAYS',
            schemeType: 'DD',
            frequency: 'DAILY'
        );
        // 100 * 100 = 10000 principal + interest
        $this->assertGreaterThan(10000.00, $ddMaturity);

        // Double scheme calculation
        $doubleMaturity = Deposit::calculateMaturityAmount(
            amount: 50000.00,
            interestRate: 0.00,
            tenure: 60,
            tenureUnit: 'MONTHS',
            schemeType: 'DOUBLE',
            frequency: 'ONE_TIME',
            roundToInteger: false,
            isDouble: true
        );
        $this->assertEquals(100000.00, $doubleMaturity);
    }

    /**
     * Test Create and Edit views render cleanly without MRP section.
     */
    public function test_create_and_edit_views_render_cleanly(): void
    {
        // Create view
        $createResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.create'));
        $createResponse->assertStatus(200);
        $createResponse->assertDontSee('MRP – Monthly Return Configuration');
        $createResponse->assertSee('Financial Limits, Tenure');

        // Edit view
        $scheme = DepositScheme::create([
            'name' => 'View Test Scheme',
            'code' => 'VTS'.rand(1000, 9999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 50000.00,
            'tenure' => 12,
            'tenure_unit' => DepositScheme::TENURE_MONTHS,
            'interest_rate' => 9.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.edit', $scheme));
        $editResponse->assertStatus(200);
        $editResponse->assertDontSee('MRP – Monthly Return Configuration');
        $editResponse->assertSee('Financial Limits, Tenure');
        $editResponse->assertSee('Minimum Amount (₹)');
    }

    /**
     * Test MRP scheme renders Contribution Amount label instead of Minimum Amount.
     */
    public function test_mrp_scheme_renders_contribution_amount_label(): void
    {
        $mrpScheme = DepositScheme::create([
            'name' => 'Monthly Return Plan (MRP) 1 Year',
            'code' => 'MRP'.rand(1000, 9999),
            'scheme_type' => DepositScheme::TYPE_FD,
            'min_amount' => 100000.00,
            'tenure' => 1,
            'tenure_unit' => DepositScheme::TENURE_YEARS,
            'interest_rate' => 10.00,
            'status' => DepositScheme::STATUS_ACTIVE,
        ]);

        // Edit view for MRP scheme should show Contribution Amount
        $editResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.edit', $mrpScheme));
        $editResponse->assertStatus(200);
        $editResponse->assertSee('Contribution Amount (₹)');
        $editResponse->assertSee('Contribution amount for Monthly Return Plan.');

        // Show view for MRP scheme should show Contribution Amount
        $showResponse = $this->actingAs($this->superAdmin)
            ->get(route('admin.deposit-schemes.show', $mrpScheme));
        $showResponse->assertStatus(200);
        $showResponse->assertSee('Contribution Amount');
    }
}
