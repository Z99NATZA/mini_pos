<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

/**
 * Builds relative URL paths for uploaded images.
 */
class ImageUrl
{
    public static function product(?string $filename): ?string
    {
        return $filename ? '/uploads/products/' . $filename : null;
    }

    public static function user(?string $filename): ?string
    {
        return $filename ? '/uploads/users/' . $filename : null;
    }
}
