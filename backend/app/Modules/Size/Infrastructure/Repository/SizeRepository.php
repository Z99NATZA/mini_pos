<?php

declare(strict_types=1);

namespace App\Modules\Size\Infrastructure\Repository;

use PDO;

/**
 * Handles all database interactions for the Size module.
 */
class SizeRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Returns a paginated list of sizes.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function findAll(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM sizes");
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT id, name, price, created_at
             FROM sizes
             ORDER BY created_at DESC
             LIMIT :limit OFFSET :offset',
        );
        $stmt->bindValue(":limit", $perPage, PDO::PARAM_INT);
        $stmt->bindValue(":offset", $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            "items" => array_map([$this, "normalizeRow"], $stmt->fetchAll()),
            "total" => $total,
        ];
    }

    /**
     * Returns all sizes without pagination (for order dropdowns).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllNoPagination(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, price FROM sizes ORDER BY name ASC",
        );
        $stmt->execute();
        return array_map([$this, "normalizeRow"], $stmt->fetchAll());
    }

    /**
     * Inserts a new size and returns the created row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO sizes (name, price)
             VALUES (:name, :price)
             RETURNING id, name, price, created_at',
        );
        $stmt->execute([":name" => $data["name"], ":price" => $data["price"]]);
        return $this->normalizeRow($stmt->fetch());
    }

    /**
     * Updates an existing size and returns the updated row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>|false
     */
    public function update(int $id, array $data): array|false
    {
        $stmt = $this->pdo->prepare(
            'UPDATE sizes
             SET name = :name, price = :price, updated_at = NOW()
             WHERE id = :id
             RETURNING id, name, price, created_at',
        );
        $stmt->execute([
            ":name" => $data["name"],
            ":price" => $data["price"],
            ":id" => $id,
        ]);
        return $this->normalizeRow($stmt->fetch());
    }

    /**
     * Deletes a size by ID.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare("DELETE FROM sizes WHERE id = :id");
        $stmt->execute([":id" => $id]);
    }

    /**
     * Casts DECIMAL/NUMERIC columns that pdo_pgsql returns as strings back to
     * their correct PHP scalar types so JavaScript receives proper numbers.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row): array
    {
        $row["price"] = (float) ($row["price"] ?? 0);
        return $row;
    }
}
