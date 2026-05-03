<?php

declare(strict_types=1);

namespace App\Core\Init;

use PDO;
use Throwable;

/**
 * Seeds the default admin user the very first time the application starts.
 * A lock file prevents the insert from running on subsequent requests.
 */
class DefaultUser
{
    private const LOCK_FILE = __DIR__ . '/../../../../storage/init.lock';

    /**
     * Inserts the default admin user if the lock file does not exist yet.
     * Uses ON CONFLICT DO NOTHING so the operation is always safe to run.
     */
    public static function ensure(PDO $pdo): void
    {
        if (file_exists(self::LOCK_FILE)) {
            return;
        }

        try {
            $hashedPassword = password_hash('pass123', PASSWORD_BCRYPT);

            $stmt = $pdo->prepare(
                'INSERT INTO users (username, name, password, role)
                 VALUES (:username, :name, :password, :role)
                 ON CONFLICT (username) DO NOTHING'
            );

            $stmt->execute([
                ':username' => 'mini_pos',
                ':name'     => 'Mini POS',
                ':password' => $hashedPassword,
                ':role'     => 'admin',
            ]);

            // Create the lock file so this block is skipped on all future requests.
            $lockDir = dirname(self::LOCK_FILE);
            if (!is_dir($lockDir)) {
                mkdir($lockDir, 0755, true);
            }

            file_put_contents(self::LOCK_FILE, date('Y-m-d H:i:s'));
        } catch (Throwable $e) {
            // Log the error but do not crash the application.
            error_log('DefaultUser::ensure failed: ' . $e->getMessage());
        }
    }
}
