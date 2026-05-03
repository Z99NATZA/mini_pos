<?php

declare(strict_types=1);

namespace App\Modules\Product\Domain\Entity;

/**
 * Immutable value object representing a product.
 */
class Product
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $name,
        public readonly float   $price,
        public readonly ?string $image,
        public readonly string  $created_at,
    ) {}

    /**
     * Creates a Product from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:         (int) $row['id'],
            name:       (string) $row['name'],
            price:      (float) $row['price'],
            image:      isset($row['image']) ? (string) $row['image'] : null,
            created_at: (string) ($row['created_at'] ?? ''),
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
            'id'         => $this->id,
            'name'       => $this->name,
            'price'      => $this->price,
            'image'      => $this->image,
            'created_at' => $this->created_at,
        ];
    }
}
