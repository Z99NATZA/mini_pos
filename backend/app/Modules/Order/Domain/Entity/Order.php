<?php

declare(strict_types=1);

namespace App\Modules\Order\Domain\Entity;

/**
 * Immutable value object representing a completed sales order.
 */
class Order
{
    public function __construct(
        public readonly int    $id,
        public readonly string $order_number,
        public readonly string $cashier_name,
        public readonly float  $total_amount,
        public readonly float  $received_amount,
        public readonly float  $change_amount,
        public readonly string $created_at,
    ) {}

    /**
     * Creates an Order from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:              (int) $row['id'],
            order_number:    (string) $row['order_number'],
            cashier_name:    (string) $row['cashier_name'],
            total_amount:    (float) $row['total_amount'],
            received_amount: (float) $row['received_amount'],
            change_amount:   (float) $row['change_amount'],
            created_at:      (string) ($row['created_at'] ?? ''),
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
            'order_number'    => $this->order_number,
            'cashier_name'    => $this->cashier_name,
            'total_amount'    => $this->total_amount,
            'received_amount' => $this->received_amount,
            'change_amount'   => $this->change_amount,
            'created_at'      => $this->created_at,
        ];
    }
}
