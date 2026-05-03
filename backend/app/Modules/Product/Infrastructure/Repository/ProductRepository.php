<?php

declare(strict_types=1);

namespace App\Modules\Product\Infrastructure\Repository;

use PDO;

/**
 * Handles all database interactions for the Product module.
 */
class ProductRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Returns a paginated list of products, optionally filtered by a search term.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function findAll(int $page, int $perPage, string $search = ''): array
    {
        $offset = ($page - 1) * $perPage;

        if ($search !== '') {
            $like = '%' . $search . '%';

            $countStmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM products WHERE name ILIKE :search'
            );
            $countStmt->execute([':search' => $like]);

            $stmt = $this->pdo->prepare(
                'SELECT id, name, price, image, created_at
                 FROM products
                 WHERE name ILIKE :search
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset'
            );
            $stmt->bindValue(':search', $like);
        } else {
            $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM products');
            $countStmt->execute();

            $stmt = $this->pdo->prepare(
                'SELECT id, name, price, image, created_at
                 FROM products
                 ORDER BY created_at DESC
                 LIMIT :limit OFFSET :offset'
            );
        }

        $total = (int) $countStmt->fetchColumn();

        $stmt->bindValue(':limit',  $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset,  PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(),
            'total' => $total,
        ];
    }

    /**
     * Returns a single product by its ID, or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, name, price, image, created_at FROM products WHERE id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Returns a product by name, optionally excluding a specific ID (for uniqueness checks on update).
     *
     * @return array<string, mixed>|null
     */
    public function findByName(string $name, ?int $excludeId = null): ?array
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM products WHERE name = :name AND id != :exclude_id LIMIT 1'
            );
            $stmt->execute([':name' => $name, ':exclude_id' => $excludeId]);
        } else {
            $stmt = $this->pdo->prepare(
                'SELECT id FROM products WHERE name = :name LIMIT 1'
            );
            $stmt->execute([':name' => $name]);
        }

        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /**
     * Inserts a new product and returns the created row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO products (name, price, image)
             VALUES (:name, :price, :image)
             RETURNING id, name, price, image, created_at'
        );

        $stmt->execute([
            ':name'  => $data['name'],
            ':price' => $data['price'],
            ':image' => $data['image'] ?? null,
        ]);

        return $stmt->fetch();
    }

    /**
     * Updates an existing product and returns the updated row.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        $stmt = $this->pdo->prepare(
            'UPDATE products
             SET name = :name, price = :price, image = :image, updated_at = NOW()
             WHERE id = :id
             RETURNING id, name, price, image, created_at'
        );

        $stmt->execute([
            ':name'  => $data['name'],
            ':price' => $data['price'],
            ':image' => $data['image'] ?? null,
            ':id'    => $id,
        ]);

        return $stmt->fetch();
    }

    /**
     * Deletes a product by ID.
     */
    public function delete(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM products WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
