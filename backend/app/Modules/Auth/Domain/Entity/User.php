<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Entity;

/**
 * Immutable value object representing an authenticated user.
 */
class User
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $username,
        public readonly string  $name,
        public readonly string  $role,
        public readonly ?string $image,
    ) {}

    /**
     * Creates a User instance from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            id:       (int) $row['id'],
            username: (string) $row['username'],
            name:     (string) $row['name'],
            role:     (string) $row['role'],
            image:    isset($row['image']) ? (string) $row['image'] : null,
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
            'id'       => $this->id,
            'username' => $this->username,
            'name'     => $this->name,
            'role'     => $this->role,
            'image'    => $this->image,
        ];
    }
}
