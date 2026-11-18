<?php

namespace Tests\Feature;

use App\Models\DepositScheme;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DepositSchemeCodeGenerationFeatureTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test full code generation with database existence checks.
     */
    public function test_automatic_code_generation_matches_required_formats(): void
    {
        $code1 = DepositScheme::generateSchemeCode('FD', 'Kissan Dhan Samrudhi Plan', 5, 'YEARS');
        $this->assertSame('FDKDSP5Y', $code1);

        $code2 = DepositScheme::generateSchemeCode('RD', 'Savings', 12, 'MONTHS');
        $this->assertSame('RDSV12M', $code2);

        $code3 = DepositScheme::generateSchemeCode('FD', 'Monthly Income', 6, 'MONTHS');
        $this->assertSame('FDMI6M', $code3);
    }

    /**
     * Test duplicate prevention by appending unique suffix when a scheme code already exists.
     */
    public function test_duplicate_codes_are_automatically_resolved_with_suffix(): void
    {
        DepositScheme::firstOrCreate(
            ['code' => 'FD1Y'],
            [
                'name' => 'FD 1 Year Existing',
                'scheme_type' => 'FD',
                'interest_rate' => 7.5,
                'min_term_months' => 12,
                'max_term_months' => 12,
                'status' => 'active',
            ]
        );

        // Generating code for another 'FD 1 Year' should return 'FD1Y-1'.
        $code = DepositScheme::generateSchemeCode('FD', 'FD 1 Year (12 Months)', 1, 'YEARS');
        $this->assertSame('FD1Y-1', $code);
    }

    /**
     * Test the preview-code endpoint returns proper JSON.
     */
    public function test_preview_code_endpoint_returns_generated_code_and_uniqueness(): void
    {
        $admin = User::first() ?? User::create([
            'name' => 'Admin Test User',
            'email' => 'admin_preview_'.uniqid().'@example.com',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.deposit-schemes.preview-code', [
            'name' => 'Kissan Dhan Samrudhi Plan',
            'scheme_type' => 'FD',
            'tenure' => 5,
            'tenure_unit' => 'YEARS',
        ]));

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'code' => 'FDKDSP5Y',
            'generated_code' => 'FDKDSP5Y',
            'is_unique' => true,
        ]);
    }
}
