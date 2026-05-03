<?php

declare(strict_types=1);

namespace App\Core\Database;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PDO database connection singleton.
 * Reads connection parameters from environment variables.
 */
class Connection
{
    private static ?PDO $instance = null;

    private function __construct() {}

    /**
     * Returns the shared PDO instance, creating it on first call.
     *
     * @throws RuntimeException when the connection cannot be established.
     */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host     = $_ENV['DB_HOST']     ?? 'localhost';
            $port     = $_ENV['DB_PORT']     ?? '5432';
            $name     = $_ENV['DB_NAME']     ?? 'mini_pos';
            $user     = $_ENV['DB_USER']     ?? 'mini_pos';
            $password = $_ENV['DB_PASSWORD'] ?? '';

            $dsn = "pgsql:host={$host};port={$port};dbname={$name}";

            try {
                $pdo = new PDO($dsn, $user, $password, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);

                self::$instance = $pdo;
            } catch (PDOException $e) {
                throw new RuntimeException(
                    'Database connection failed: ' . $e->getMessage(),
                    (int) $e->getCode(),
                    $e
                );
            }
        }

        return self::$instance;
    }
}
