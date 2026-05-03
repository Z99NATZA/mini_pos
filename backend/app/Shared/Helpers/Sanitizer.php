<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

/**
 * Provides static methods for sanitizing user input before processing.
 */
class Sanitizer
{
    /**
     * Strips HTML tags and converts special characters to HTML entities.
     */
    public static function string(string $value): string
    {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /**
     * Casts a value to a safe integer.
     */
    public static function int(mixed $value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * Strips non-numeric characters (except '.') and rounds to 2 decimal places.
     */
    public static function float(mixed $value): float
    {
        // Remove everything that is not a digit or a decimal point.
        $cleaned = preg_replace('/[^0-9.]/', '', (string) $value) ?? '0';
        return round((float) $cleaned, 2);
    }

    /**
     * Sanitizes a monetary string value (removes commas, currency symbols, etc.)
     * and rounds the result to 2 decimal places.
     */
    public static function money(string $value): float
    {
        // Remove commas and any characters that are not digits or a decimal point.
        $cleaned = preg_replace('/[^0-9.]/', '', str_replace(',', '', $value)) ?? '0';
        return round((float) $cleaned, 2);
    }
}
