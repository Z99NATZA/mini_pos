<?php

declare(strict_types=1);

namespace App\Core\Init;

use PDO;
use Throwable;

/**
 * Applies the database schema the very first time the container starts.
 *
 * All DDL statements in schema.sql use CREATE TABLE IF NOT EXISTS, so this
 * operation is idempotent and safe to run on every container restart
 * (which happens on ephemeral hosting such as Render's free tier).
 *
 * A lock file prevents repeated runs within the same container lifetime,
 * reducing unnecessary database round-trips.
 */
class SchemaInit
{
    private const LOCK_FILE   = __DIR__ . '/../../../../storage/schema.lock';
    private const SCHEMA_FILE = __DIR__ . '/../../../../database/schema.sql';

    /**
     * Runs the schema SQL against the given PDO connection unless the lock
     * file already exists, in which case it returns immediately.
     */
    public static function ensure(PDO $pdo): void
    {
        if (file_exists(self::LOCK_FILE)) {
            return;
        }

        try {
            $sql = file_get_contents(self::SCHEMA_FILE);

            if ($sql === false) {
                error_log('SchemaInit::ensure — could not read schema file.');
                return;
            }

            $pdo->exec($sql);

            // Write the lock file so subsequent requests in this container
            // lifetime skip the schema run entirely.
            $lockDir = dirname(self::LOCK_FILE);
            if (!is_dir($lockDir)) {
                mkdir($lockDir, 0755, true);
            }

            file_put_contents(self::LOCK_FILE, date('Y-m-d H:i:s'));
        } catch (Throwable $e) {
            error_log('SchemaInit::ensure failed: ' . $e->getMessage());
        }
    }
}
