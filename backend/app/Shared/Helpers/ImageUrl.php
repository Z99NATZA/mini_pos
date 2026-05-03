<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

/**
 * Builds URL paths for uploaded images.
 *
 * In production set APP_URL to the full backend origin, e.g.
 * https://mini-pos-backend.onrender.com
 * so the frontend (on a different domain) can load images correctly.
 * Leave APP_URL empty for local development — relative URLs will be used.
 */
class ImageUrl
{
    private static function base(): string
    {
        return rtrim($_ENV["APP_URL"] ?? "", "/");
    }

    public static function product(?string $filename): ?string
    {
        return $filename
            ? self::base() . "/uploads/products/" . $filename
            : null;
    }

    public static function user(?string $filename): ?string
    {
        return $filename ? self::base() . "/uploads/users/" . $filename : null;
    }
}
