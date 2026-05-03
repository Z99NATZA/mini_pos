<?php

declare(strict_types=1);

namespace App\Shared\Helpers;

use RuntimeException;

/**
 * Minimal Supabase Storage client using PHP stream wrappers (no extra dependencies).
 *
 * Required env vars:
 *   SUPABASE_URL         e.g. https://xxxx.supabase.co
 *   SUPABASE_SERVICE_KEY service_role key
 */
class SupabaseStorage
{
    private static function url(): string
    {
        return rtrim($_ENV['SUPABASE_URL'] ?? '', '/');
    }

    private static function key(): string
    {
        return $_ENV['SUPABASE_SERVICE_KEY'] ?? '';
    }

    /**
     * Returns true when Supabase env vars are configured.
     */
    public static function isConfigured(): bool
    {
        return self::url() !== '' && self::key() !== '';
    }

    /**
     * Uploads a file to Supabase Storage and returns its public URL.
     *
     * @throws RuntimeException on failure.
     */
    public static function upload(
        string $bucket,
        string $path,
        string $tmpFile,
        string $mimeType,
    ): string {
        $endpoint    = self::url() . '/storage/v1/object/' . $bucket . '/' . $path;
        $fileContent = file_get_contents($tmpFile);

        if ($fileContent === false) {
            throw new RuntimeException('Could not read temp file for Supabase upload.');
        }

        $context = stream_context_create([
            'http' => [
                'method'        => 'POST',
                'header'        => implode("\r\n", [
                    'Authorization: Bearer ' . self::key(),
                    'Content-Type: ' . $mimeType,
                    'Content-Length: ' . strlen($fileContent),
                    'x-upsert: true',
                ]),
                'content'       => $fileContent,
                'ignore_errors' => true,
            ],
        ]);

        $result = file_get_contents($endpoint, false, $context);
        $status = 0;

        foreach ($http_response_header as $header) {
            if (preg_match('/HTTP\/[\d.]+\s+(\d+)/', $header, $m)) {
                $status = (int) $m[1];
            }
        }

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException(
                'Supabase Storage upload failed (HTTP ' . $status . '): ' . $result,
            );
        }

        return self::publicUrl($bucket, $path);
    }

    /**
     * Deletes a file from Supabase Storage. Fails silently.
     */
    public static function delete(string $bucket, string $path): void
    {
        try {
            $endpoint = self::url() . '/storage/v1/object/' . $bucket;
            $body     = json_encode(['prefixes' => [$path]], JSON_THROW_ON_ERROR);

            $context = stream_context_create([
                'http' => [
                    'method'        => 'DELETE',
                    'header'        => implode("\r\n", [
                        'Authorization: Bearer ' . self::key(),
                        'Content-Type: application/json',
                        'Content-Length: ' . strlen($body),
                    ]),
                    'content'       => $body,
                    'ignore_errors' => true,
                ],
            ]);

            file_get_contents($endpoint, false, $context);
        } catch (\Throwable $e) {
            error_log('SupabaseStorage::delete failed: ' . $e->getMessage());
        }
    }

    /**
     * Builds the public CDN URL for an object in a public bucket.
     */
    public static function publicUrl(string $bucket, string $path): string
    {
        return self::url() . '/storage/v1/object/public/' . $bucket . '/' . $path;
    }
}
