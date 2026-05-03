<?php

declare(strict_types=1);

namespace App\Modules\User\Infrastructure\Repository;

use PDO;

/**
 * Handles all database interactions for the User module.
 */
class UserRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Returns a paginated list of users (passwords excluded).
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function findAll(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM users');
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT id, username, name, role, image, created_at
             FROM users
             ORDER BY created_at ASC
             LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    /**
     * Returns a single user by ID (password excluded), or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, username, name, role, image, created_at FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Returns a user by username, optionally excluding a specific ID (for uniqueness checks on update).
     *
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username, ?int $excludeId = null): ?array
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM users WHERE username = :username AND id != :exclude_id LIMIT 1'
            );
            $stmt->execute([':username' => $username, ':exclude_id' => $excludeId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM users WHERE username = :username LIMIT 1'
            );
            $stmt->execute([':username' => $username]);
        }

        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Returns the count of admin users.
     */
    public function countAdmins(): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    /**
     * Inserts a new user and returns the created row (without password).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, name, password, role, image)
             VALUES (:username, :name, :password, :role, :image)
             RETURNING id, username, name, role, image, created_at'
        );

        $stmt->execute([
            ':username' => $data['username'],
            ':name'     => $data['name'],
            ':password' => $data['password'],
            ':role'     => $data['role'],
            ':image'    => $data['image'] ?? null,
        ]);

        return $stmt->fetch();
    }

    /**
     * Updates an existing user and returns the updated row (without password).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|false
     */
    public function update(int $id, array $data): array|false
    {
        // Build the SET clause dynamically based on provided fields.
        $sets   = ['username = :username', 'name = :name', 'role = :role', 'updated_at = NOW()'];
        $params = [
            ':username' => $data['username'],
            ':name'     => $data['name'],
            ':role'     => $data['role'],
            ':id'       => $id,
        ];

        if (!empty($data['password'])) {
            $sets[]              = 'password = :password';
            $params[':password'] = $data['password'];
        }

        if (array_key_exists('image', $data)) {
            $sets[]           = 'image = :image';
            $params[':image'] = $data['image'];
        }

        $sql = 'UPDATE users SET ' . implode(', ', $sets) . ' WHERE id = :id
                RETURNING id, username, name, role, image, created_at';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch();
    }

    /**
     * Deletes a user by ID.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
