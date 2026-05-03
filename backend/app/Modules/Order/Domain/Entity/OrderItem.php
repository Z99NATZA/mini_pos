<?php

declare(strict_types=1);

namespace App\Modules\Order\Domain\Entity;

/**
 * Immutable value object representing a single line item within an order.
 */
class OrderItem
{
    public function __construct(
        public readonly int    $id,
        public readonly int    $order_id,
        public readonly string $order_item_code,
        public readonly string $product_name,
        public readonly float  $product_price,
        public readonly string $size_name,
        public readonly float  $size_price,
        public readonly string $type_name,
        public readonly float  $type_price,
        public readonly int    $quantity,
        public readonly float  $amount,
    ) {}

    /**
     * Creates an OrderItem from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:              (int) $row['id'],
            order_id:        (int) $row['order_id'],
            order_item_code: (string) $row['order_item_code'],
            product_name:    (string) $row['product_name'],
            product_price:   (float) $row['product_price'],
            size_name:       (string) ($row['size_name'] ?? ''),
            size_price:      (float) ($row['size_price'] ?? 0),
            type_name:       (string) ($row['type_name'] ?? ''),
            type_price:      (float) ($row['type_price'] ?? 0),
            quantity:        (int) $row['quantity'],
            amount:          (float) $row['amount'],
        );
    }

    /**
     * Returns a plain array suitable for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'              => $this->id,
            'order_id'        => $this->order_id,
            'order_item_code' => $this->order_item_code,
            'product_name'    => $this->product_name,
            'product_price'   => $this->product_price,
            'size_name'       => $this->size_name,
            'size_price'      => $this->size_price,
            'type_name'       => $this->type_name,
            'type_price'      => $this->type_price,
            'quantity'        => $this->quantity,
            'amount'          => $this->amount,
        ];
    }
}
