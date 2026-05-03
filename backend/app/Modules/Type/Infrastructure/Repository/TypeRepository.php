<?php

declare(strict_types=1);

namespace App\Modules\Type\Infrastructure\Repository;

use PDO;

/**
 * Handles all database interactions for the Type module.
 */
class TypeRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Returns a paginated list of types.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function findAll(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM types');
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT id, name, price, created_at
             FROM types
             ORDER BY created_at DESC
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
     * Returns all types without pagination (for order dropdowns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllNoPagination(): array
    {
        $stmt = $this->pdo->prepare('SELECT id, name, price FROM types ORDER BY name ASC');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Inserts a new type and returns the created row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO types (name, price)
             VALUES (:name, :price)
             RETURNING id, name, price, created_at'
        );
        $stmt->execute([':name' => $data['name'], ':price' => $data['price']]);
        return $stmt->fetch();
    }

    /**
     * Updates an existing type and returns the updated row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|false
     */
    public function update(int $id, array $data): array|false
    {
        $stmt = $this->pdo->prepare(
            'UPDATE types
             SET name = :name, price = :price, updated_at = NOW()
             WHERE id = :id
             RETURNING id, name, price, created_at'
        );
        $stmt->execute([':name' => $data['name'], ':price' => $data['price'], ':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Deletes a type by ID.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM types WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
