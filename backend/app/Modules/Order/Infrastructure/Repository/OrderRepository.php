<?php

declare(strict_types=1);

namespace App\Modules\Order\Infrastructure\Repository;

use PDO;

/**
 * Handles all database interactions for the Order module.
 */
class OrderRepository
{
    public function __construct(private readonly PDO $pdo) {}

    /**
     * Returns a paginated list of orders with item count.
     *
     * @return array{items: array<int, array<string, mixed>>, total: int}
     */
    public function findAll(int $page, int $perPage): array
    {
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare('SELECT COUNT(*) FROM orders');
        $countStmt->execute();
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.order_number, o.cashier_name, o.total_amount,
                    o.received_amount, o.change_amount, o.created_at,
                    COUNT(oi.id) AS item_count
             FROM orders o
             LEFT JOIN order_items oi ON oi.order_id = o.id
             GROUP BY o.id
             ORDER BY o.created_at DESC
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
     * Returns a single order with its items and toppings, or null if not found.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        // Fetch the order header.
        $orderStmt = $this->pdo->prepare(
            'SELECT id, order_number, cashier_name, total_amount,
                    received_amount, change_amount, created_at
             FROM orders
             WHERE id = :id
             LIMIT 1'
        );
        $orderStmt->execute([':id' => $id]);
        $order = $orderStmt->fetch();

        if ($order === false) {
            return null;
        }

        // Fetch order items.
        $itemStmt = $this->pdo->prepare(
            'SELECT id, order_id, order_item_code, product_name, product_price,
                    size_name, size_price, type_name, type_price, quantity, amount
             FROM order_items
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $itemStmt->execute([':order_id' => $id]);
        $items = $itemStmt->fetchAll();

        // Fetch toppings for each item.
        $toppingStmt = $this->pdo->prepare(
            'SELECT order_item_code, topping_name, topping_price
             FROM order_item_toppings
             WHERE order_id = :order_id
             ORDER BY id ASC'
        );
        $toppingStmt->execute([':order_id' => $id]);
        $toppings = $toppingStmt->fetchAll();

        // Group toppings by order_item_code.
        $toppingMap = [];
        foreach ($toppings as $topping) {
            $toppingMap[$topping['order_item_code']][] = $topping;
        }

        // Attach toppings to their respective items.
        foreach ($items as &$item) {
            $item['toppings'] = $toppingMap[$item['order_item_code']] ?? [];
        }
        unset($item);

        $order['items'] = $items;

        return $order;
    }

    /**
     * Returns the most recent order number that starts with today's date prefix.
     * Used to generate sequential order numbers within a day.
     */
    public function getLastOrderNumberToday(): ?string
    {
        $today = date('Ymd');

        $stmt = $this->pdo->prepare(
            "SELECT order_number
             FROM orders
             WHERE order_number LIKE :prefix
             ORDER BY order_number DESC
             LIMIT 1"
        );
        $stmt->execute([':prefix' => $today . '%']);
        $row = $stmt->fetch();

        return $row !== false ? (string) $row['order_number'] : null;
    }

    /**
     * Inserts a new order header row and returns the new order ID.
     *
     * @param array<string, mixed> $data
     */
    public function createOrder(array $data): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO orders (order_number, cashier_name, total_amount, received_amount, change_amount)
             VALUES (:order_number, :cashier_name, :total_amount, :received_amount, :change_amount)
             RETURNING id'
        );

        $stmt->execute([
            ':order_number'    => $data['order_number'],
            ':cashier_name'    => $data['cashier_name'],
            ':total_amount'    => $data['total_amount'],
            ':received_amount' => $data['received_amount'],
            ':change_amount'   => $data['change_amount'],
        ]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * Inserts a single order item row.
     *
     * @param array<string, mixed> $data
     */
    public function createOrderItem(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO order_items
             (order_id, order_item_code, product_name, product_price,
              size_name, size_price, type_name, type_price, quantity, amount)
             VALUES
             (:order_id, :order_item_code, :product_name, :product_price,
              :size_name, :size_price, :type_name, :type_price, :quantity, :amount)'
        );

        $stmt->execute([
            ':order_id'        => $data['order_id'],
            ':order_item_code' => $data['order_item_code'],
            ':product_name'    => $data['product_name'],
            ':product_price'   => $data['product_price'],
            ':size_name'       => $data['size_name'],
            ':size_price'      => $data['size_price'],
            ':type_name'       => $data['type_name'],
            ':type_price'      => $data['type_price'],
            ':quantity'        => $data['quantity'],
            ':amount'          => $data['amount'],
        ]);
    }

    /**
     * Inserts a single order item topping row.
     *
     * @param array<string, mixed> $data
     */
    public function createOrderItemTopping(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO order_item_toppings (order_id, order_item_code, topping_name, topping_price)
             VALUES (:order_id, :order_item_code, :topping_name, :topping_price)'
        );

        $stmt->execute([
            ':order_id'        => $data['order_id'],
            ':order_item_code' => $data['order_item_code'],
            ':topping_name'    => $data['topping_name'],
            ':topping_price'   => $data['topping_price'],
        ]);
    }

    /**
     * Deletes an order by ID.
     * Child rows in order_items and order_item_toppings are removed via ON DELETE CASCADE.
     */
    public function deleteOrder(int $id): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM orders WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
