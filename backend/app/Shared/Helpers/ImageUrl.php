<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

/**
 * Builds URL paths for uploaded images.
 *
 * Handles two storage backends transparently:
 *  - Supabase Storage: value is already a full https:// URL → returned as-is.
 *  - Local filesystem: value is a bare filename → prefixed with APP_URL.
 *
 * In production set APP_URL to the full backend origin, e.g.
 * https://mini-pos-backend-rqg2.onrender.com
 */
class ImageUrl
{
    private static function base(): string
    {
        return rtrim($_ENV["APP_URL"] ?? "", "/");
    }

    public static function product(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        // Supabase Storage or any absolute URL — return as-is.
        if (str_starts_with($value, "http")) {
            return $value;
        }
        // Legacy local filename.
        return self::base() . "/uploads/products/" . $value;
    }

    public static function user(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        if (str_starts_with($value, "http")) {
            return $value;
        }
        return self::base() . "/uploads/users/" . $value;
    }
}
