<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCase;

use App\Modules\User\Infrastructure\Repository\UserRepository;
use InvalidArgumentException;

/**
 * Handles business logic for updating an existing application user.
 */
class UpdateUserUseCase
{
    private const VALID_ROLES  = ['admin', 'staff'];
    private const MIN_PASS_LEN = 6;
    private const MAX_PASS_LEN = 50;

    public function __construct(private readonly UserRepository $repository) {}

    /**
     * Validates input and updates the user.
     * Password is optional; if provided it must pass length rules and will be hashed.
     *
     * @return array<string, mixed> The updated user row (without password).
     * @throws InvalidArgumentException when validation fails.
     */
    public function execute(
        int     $id,
        string  $username,
        string  $name,
        string  $password,
        string  $role,
        ?string $imageName = null,
    ): array {
        // Validate username.
        if (empty($username)) {
            throw new InvalidArgumentException('Username is required.');
        }

        // Validate name.
        if (empty($name)) {
            throw new InvalidArgumentException('Name is required.');
        }

        // Validate role.
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException('Role must be "admin" or "staff".');
        }

        // Ensure user exists.
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('User not found.');
        }

        // Ensure username uniqueness, excluding the current user.
        if ($this->repository->findByUsername($username, $id) !== null) {
            throw new InvalidArgumentException('Another user with this username already exists.');
        }

        $data = [
            'username' => $username,
            'name'     => $name,
            'role'     => $role,
        ];

        // Password is optional on update. Only update if a new one was provided.
        if (!empty($password)) {
            if (strlen($password) < self::MIN_PASS_LEN) {
                throw new InvalidArgumentException(
                    'Password must be at least ' . self::MIN_PASS_LEN . ' characters.'
                );
            }

            if (strlen($password) > self::MAX_PASS_LEN) {
                throw new InvalidArgumentException(
                    'Password must not exceed ' . self::MAX_PASS_LEN . ' characters.'
                );
            }

            $data['password'] = password_hash($password, PASSWORD_BCRYPT);
        }

        // Update image only if a new one was provided; otherwise keep the existing image.
        $data['image'] = $imageName ?? $existing['image'];

        $updated = $this->repository->update($id, $data);

        if ($updated === false) {
            throw new \RuntimeException('Failed to update user.');
        }

        return $updated;
    }
}
