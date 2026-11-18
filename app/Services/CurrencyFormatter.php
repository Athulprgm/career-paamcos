<?php

namespace App\Services;

class CurrencyFormatter
{
    /**
     * Units and teens lookup for words conversion.
     *
     * @var array<int, string>
     */
    protected static array $ones = [
        0 => '',
        1 => 'One',
        2 => 'Two',
        3 => 'Three',
        4 => 'Four',
        5 => 'Five',
        6 => 'Six',
        7 => 'Seven',
        8 => 'Eight',
        9 => 'Nine',
        10 => 'Ten',
        11 => 'Eleven',
        12 => 'Twelve',
        13 => 'Thirteen',
        14 => 'Fourteen',
        15 => 'Fifteen',
        16 => 'Sixteen',
        17 => 'Seventeen',
        18 => 'Eighteen',
        19 => 'Nineteen',
    ];

    /**
     * Tens lookup for words conversion.
     *
     * @var array<int, string>
     */
    protected static array $tens = [
        2 => 'Twenty',
        3 => 'Thirty',
        4 => 'Forty',
        5 => 'Fifty',
        6 => 'Sixty',
        7 => 'Seventy',
        8 => 'Eighty',
        9 => 'Ninety',
    ];

    /**
     * Format an amount into compact Indian text denomination (e.g. 15 Crore, 7.5 Crore, 50 Lakh).
     */
    public static function formatText(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $num = (float) $amount;
        if ($num <= 0) {
            return '0';
        }

        if ($num >= 10000000) {
            $val = $num / 10000000;

            return self::trimDecimals($val).' Crore';
        }

        if ($num >= 100000) {
            $val = $num / 100000;

            return self::trimDecimals($val).' Lakh';
        }

        if ($num >= 1000) {
            $val = $num / 1000;

            return self::trimDecimals($val).' Thousand';
        }

        return self::trimDecimals($num);
    }

    /**
     * Convert an integer/float amount into Indian currency words (e.g. Fifteen Crore, Seven Crore Fifty Lakh).
     */
    public static function toWords(float|int|string|null $amount, bool $withRupees = false): string
    {
        if ($amount === null || $amount === '') {
            return '';
        }

        $floatAmount = (float) $amount;
        if ($floatAmount <= 0) {
            return $withRupees ? 'Zero Rupees' : 'Zero';
        }

        $whole = (int) floor($floatAmount);
        $fraction = (int) round(($floatAmount - $whole) * 100);

        $words = self::convertWholeNumberToWords($whole);

        if ($words === '') {
            $words = 'Zero';
        }

        if ($withRupees) {
            $words .= ' Rupees';
            if ($fraction > 0) {
                $words .= ' and '.self::convertWholeNumberToWords($fraction).' Paise';
            }
        } elseif ($fraction > 0) {
            $words .= ' Point '.self::convertWholeNumberToWords($fraction);
        }

        return trim($words);
    }

    /**
     * Format amount into Indian comma grouped number string (e.g. 15,00,00,000.00).
     */
    public static function formatIndianNumber(float|int|string|null $amount, int $decimals = 2): string
    {
        if ($amount === null || $amount === '') {
            return '0.00';
        }

        $num = (float) $amount;
        $isNegative = $num < 0;
        $num = abs($num);

        $whole = (int) floor($num);
        $fraction = round($num - $whole, $decimals);
        $fractionStr = substr(number_format($fraction, $decimals), 1); // e.g. ".00"

        $wholeStr = (string) $whole;
        if (strlen($wholeStr) > 3) {
            $lastThree = substr($wholeStr, -3);
            $rest = substr($wholeStr, 0, -3);
            $rest = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $rest);
            $formattedWhole = $rest.','.$lastThree;
        } else {
            $formattedWhole = $wholeStr;
        }

        return ($isNegative ? '-' : '').$formattedWhole.($decimals > 0 ? $fractionStr : '');
    }

    /**
     * Convert positive whole number into words using Indian Numbering System.
     */
    protected static function convertWholeNumberToWords(int $number): string
    {
        if ($number === 0) {
            return '';
        }

        $crore = (int) floor($number / 10000000);
        $number %= 10000000;

        $lakh = (int) floor($number / 100000);
        $number %= 100000;

        $thousand = (int) floor($number / 1000);
        $number %= 1000;

        $hundred = (int) floor($number / 100);
        $remainder = $number % 100;

        $parts = [];

        if ($crore > 0) {
            $parts[] = self::convertWholeNumberToWords($crore).' Crore';
        }

        if ($lakh > 0) {
            $parts[] = self::convertUnderHundredToWords($lakh).' Lakh';
        }

        if ($thousand > 0) {
            $parts[] = self::convertUnderHundredToWords($thousand).' Thousand';
        }

        if ($hundred > 0) {
            $parts[] = self::$ones[$hundred].' Hundred';
        }

        if ($remainder > 0) {
            $parts[] = self::convertUnderHundredToWords($remainder);
        }

        return implode(' ', $parts);
    }

    /**
     * Convert numbers less than 100 to words.
     */
    protected static function convertUnderHundredToWords(int $num): string
    {
        if ($num < 20) {
            return self::$ones[$num];
        }

        $tens = (int) floor($num / 10);
        $unit = $num % 10;

        return self::$tens[$tens].($unit > 0 ? ' '.self::$ones[$unit] : '');
    }

    /**
     * Format float to at most 2 decimal places, removing unnecessary trailing zeros.
     */
    protected static function trimDecimals(float $val): string
    {
        return rtrim(rtrim(number_format($val, 2, '.', ''), '0'), '.');
    }
}
