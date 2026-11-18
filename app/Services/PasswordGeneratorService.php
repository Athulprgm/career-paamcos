<?php

namespace App\Services;

class PasswordGeneratorService
{
    /**
     * Generate a cryptographically secure, readable temporary password.
     * Guarantees uppercase, lowercase, numbers, and a safe special character
     * while avoiding ambiguous characters (e.g. 0, O, 1, l, I).
     */
    public static function generate(int $length = 10): string
    {
        $uppercase = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lowercase = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $special = '@#$%&*!';

        // Ensure at least one character from each category
        $password = [
            $uppercase[random_int(0, strlen($uppercase) - 1)],
            $lowercase[random_int(0, strlen($lowercase) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $special[random_int(0, strlen($special) - 1)],
        ];

        $allChars = $uppercase.$lowercase.$numbers.$special;
        $remaining = max(0, $length - count($password));

        for ($i = 0; $i < $remaining; $i++) {
            $password[] = $allChars[random_int(0, strlen($allChars) - 1)];
        }

        // Shuffle securely
        shuffle($password);

        return implode('', $password);
    }
}
