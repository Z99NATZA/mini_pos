<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Repository;

use PDO;

/**
 * Handles database queries related to authentication.
 */
class AuthRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Retrieves a user row by username, including the password hash.
     *
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, name, password, role, image
             FROM users
             WHERE username = :username
             LIMIT 1'
        );

        $stmt->execute([':username' => $username]);

        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }
}
