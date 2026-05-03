<?php

declare(strict_types=1);

namespace App\Modules\Size\Domain\Entity;

/**
 * Immutable value object representing a drink/item size option.
 */
class Size
{
    public function __construct(
        public readonly int    $id,
        public readonly string $name,
        public readonly float  $price,
        public readonly string $created_at,
    ) {}

    /**
     * Creates a Size from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:         (int) $row['id'],
            name:       (string) $row['name'],
            price:      (float) $row['price'],
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
            'created_at' => $this->created_at,
        ];
    }
}
