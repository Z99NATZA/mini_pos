<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Entity;

/**
 * Immutable value object representing an application user (staff or admin).
 */
class User
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $username,
        public readonly string  $name,
        public readonly string  $role,
        public readonly ?string $image,
        public readonly string  $created_at,
    ) {}

    /**
     * Creates a User from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:         (int) $row['id'],
            username:   (string) $row['username'],
            name:       (string) $row['name'],
            role:       (string) $row['role'],
            image:      isset($row['image']) ? (string) $row['image'] : null,
            created_at: (string) ($row['created_at'] ?? ''),
        );
    }

    /**
     * Returns a plain array suitable for JSON serialization (no password).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'username'   => $this->username,
            'name'       => $this->name,
            'role'       => $this->role,
            'image'      => $this->image,
            'created_at' => $this->created_at,
        ];
    }
}
