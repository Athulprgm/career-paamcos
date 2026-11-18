<?php

namespace Tests\Unit;

use App\Services\CurrencyFormatter;
use PHPUnit\Framework\TestCase;

class CurrencyFormatterTest extends TestCase
{
    public function test_format_text_handles_crores_lakhs_and_thousands(): void
    {
        $this->assertEquals('15 Crore', CurrencyFormatter::formatText(150000000));
        $this->assertEquals('10 Crore', CurrencyFormatter::formatText(100000000));
        $this->assertEquals('7.5 Crore', CurrencyFormatter::formatText(75000000));
        $this->assertEquals('5 Crore', CurrencyFormatter::formatText(50000000));
        $this->assertEquals('3 Crore', CurrencyFormatter::formatText(30000000));
        $this->assertEquals('2 Crore', CurrencyFormatter::formatText(20000000));
        $this->assertEquals('1.5 Crore', CurrencyFormatter::formatText(15000000));
        $this->assertEquals('1 Crore', CurrencyFormatter::formatText(10000000));
        $this->assertEquals('50 Lakh', CurrencyFormatter::formatText(5000000));
        $this->assertEquals('25 Lakh', CurrencyFormatter::formatText(2500000));
        $this->assertEquals('10 Lakh', CurrencyFormatter::formatText(1000000));
        $this->assertEquals('5 Lakh', CurrencyFormatter::formatText(500000));
        $this->assertEquals('1 Lakh', CurrencyFormatter::formatText(100000));
        $this->assertEquals('50 Thousand', CurrencyFormatter::formatText(50000));
        $this->assertEquals('25 Thousand', CurrencyFormatter::formatText(25000));
        $this->assertEquals('500', CurrencyFormatter::formatText(500));
        $this->assertEquals('0', CurrencyFormatter::formatText(0));
    }

    public function test_to_words_converts_correctly(): void
    {
        $this->assertEquals('Fifteen Crore', CurrencyFormatter::toWords(150000000));
        $this->assertEquals('Ten Crore', CurrencyFormatter::toWords(100000000));
        $this->assertEquals('Seven Crore Fifty Lakh', CurrencyFormatter::toWords(75000000));
        $this->assertEquals('Five Crore', CurrencyFormatter::toWords(50000000));
        $this->assertEquals('Three Crore', CurrencyFormatter::toWords(30000000));
        $this->assertEquals('Two Crore', CurrencyFormatter::toWords(20000000));
        $this->assertEquals('One Crore Fifty Lakh', CurrencyFormatter::toWords(15000000));
        $this->assertEquals('One Crore', CurrencyFormatter::toWords(10000000));
        $this->assertEquals('Fifty Lakh', CurrencyFormatter::toWords(5000000));
        $this->assertEquals('Twenty Five Lakh', CurrencyFormatter::toWords(2500000));
        $this->assertEquals('Ten Lakh', CurrencyFormatter::toWords(1000000));
        $this->assertEquals('Five Lakh', CurrencyFormatter::toWords(500000));
        $this->assertEquals('One Lakh', CurrencyFormatter::toWords(100000));
        $this->assertEquals('Fifty Thousand', CurrencyFormatter::toWords(50000));
        $this->assertEquals('Twenty Five Thousand', CurrencyFormatter::toWords(25000));
        $this->assertEquals('Zero', CurrencyFormatter::toWords(0));
    }

    public function test_format_indian_number(): void
    {
        $this->assertEquals('15,00,00,000.00', CurrencyFormatter::formatIndianNumber(150000000));
        $this->assertEquals('7,50,00,000.00', CurrencyFormatter::formatIndianNumber(75000000));
        $this->assertEquals('1,50,00,000.00', CurrencyFormatter::formatIndianNumber(15000000));
        $this->assertEquals('50,00,000.00', CurrencyFormatter::formatIndianNumber(5000000));
        $this->assertEquals('25,000.00', CurrencyFormatter::formatIndianNumber(25000));
        $this->assertEquals('500.00', CurrencyFormatter::formatIndianNumber(500));
        $this->assertEquals('0.00', CurrencyFormatter::formatIndianNumber(0));
    }
}
