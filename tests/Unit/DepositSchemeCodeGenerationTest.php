<?php

namespace Tests\Unit;

use App\Models\DepositScheme;
use PHPUnit\Framework\TestCase;

class DepositSchemeCodeGenerationTest extends TestCase
{
    /**
     * Test Example 1 from requirement: FDKDSP5Y - FD, Kissan Dhan Samrudhi Plan, 5 Years.
     */
    public function test_it_generates_fdkdsp5y_for_kissan_dhan_samrudhi_plan_5_years(): void
    {
        $code = DepositScheme::extractAbbreviation('Kissan Dhan Samrudhi Plan', 'FD');
        $this->assertSame('KDSP', $code);

        $fullCode = 'FD'.$code.'5Y';
        $this->assertSame('FDKDSP5Y', $fullCode);
    }

    /**
     * Test Example 2 from requirement: RDSV12M - RD, Savings, 12 Months.
     */
    public function test_it_generates_rdsv12m_for_rd_savings_12_months(): void
    {
        $code = DepositScheme::extractAbbreviation('Savings', 'RD');
        $this->assertSame('SV', $code);

        $fullCode = 'RD'.$code.'12M';
        $this->assertSame('RDSV12M', $fullCode);
    }

    /**
     * Test Example 3 from requirement: FDMI6M - FD, Monthly Income, 6 Months.
     */
    public function test_it_generates_fdmi6m_for_fd_monthly_income_6_months(): void
    {
        $code = DepositScheme::extractAbbreviation('Monthly Income', 'FD');
        $this->assertSame('MI', $code);

        $fullCode = 'FD'.$code.'6M';
        $this->assertSame('FDMI6M', $fullCode);
    }

    /**
     * Test that redundant deposit type prefixes and tenure strings in the name are stripped.
     */
    public function test_it_strips_redundant_type_prefix_and_tenure_phrases(): void
    {
        $code1 = DepositScheme::extractAbbreviation('FD Kissan Dhan Samrudhi Plan 5 Year', 'FD');
        $this->assertSame('KDSP', $code1);

        $code2 = DepositScheme::extractAbbreviation('Recurring Deposit Savings (12 Months)', 'RD');
        $this->assertSame('SV', $code2);

        $code3 = DepositScheme::extractAbbreviation('FD 1 Year (12 Months)', 'FD');
        $this->assertSame('', $code3);
    }

    /**
     * Test single word consonant extraction for banking scheme abbreviations.
     */
    public function test_it_extracts_abbreviations_for_single_word_schemes(): void
    {
        $this->assertSame('SV', DepositScheme::extractAbbreviation('Savings'));
        $this->assertSame('RG', DepositScheme::extractAbbreviation('Regular'));
        $this->assertSame('GR', DepositScheme::extractAbbreviation('Growth'));
        $this->assertSame('IN', DepositScheme::extractAbbreviation('Income'));
        $this->assertSame('SM', DepositScheme::extractAbbreviation('Smart'));
    }

    /**
     * Test multi-word abbreviation extraction.
     */
    public function test_it_extracts_abbreviations_for_multi_word_schemes(): void
    {
        $this->assertSame('CEP', DepositScheme::extractAbbreviation('Child Education Plan'));
        $this->assertSame('DBIS', DepositScheme::extractAbbreviation('Double Benefit Income Structure'));
        $this->assertSame('SCS', DepositScheme::extractAbbreviation('Senior Citizen Scheme'));
    }
}
