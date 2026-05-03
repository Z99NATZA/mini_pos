<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

/**
 * Utility methods for formatting and parsing monetary values.
 */
class MoneyHelper
{
    /**
     * Formats a float amount to a string with 2 decimal places.
     *
     * Example: 1234.5 => "1234.50"
     */
    public static function format(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }

    /**
     * Converts a money string (possibly with commas or currency symbols) to a float.
     *
     * Example: "1,234.50" => 1234.50
     */
    public static function toFloat(string $value): float
    {
        $cleaned = preg_replace('/[^0-9.]/', '', str_replace(',', '', $value)) ?? '0';
        return (float) $cleaned;
    }
}
