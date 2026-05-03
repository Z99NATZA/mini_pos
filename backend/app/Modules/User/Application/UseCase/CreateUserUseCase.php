<?php

declare(strict_types=1);

namespace App\Modules\User\Application\UseCase;

use App\Modules\User\Infrastructure\Repository\UserRepository;
use InvalidArgumentException;

/**
 * Handles business logic for creating a new application user.
 */
class CreateUserUseCase
{
    private const VALID_ROLES    = ['admin', 'staff'];
    private const MIN_PASS_LEN   = 6;
    private const MAX_PASS_LEN   = 50;

    public function __construct(private readonly UserRepository $repository) {}

    /**
     * Validates the input, hashes the password, and creates the user.
     *
     * @return array<string, mixed> The created user row (without password).
     * @throws InvalidArgumentException when validation fails.
     */
    public function execute(
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

        // Validate password.
        if (empty($password)) {
            throw new InvalidArgumentException('Password is required.');
        }

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

        // Validate role.
        if (!in_array($role, self::VALID_ROLES, true)) {
            throw new InvalidArgumentException('Role must be "admin" or "staff".');
        }

        // Ensure username uniqueness.
        if ($this->repository->findByUsername($username) !== null) {
            throw new InvalidArgumentException('A user with this username already exists.');
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        return $this->repository->create([
            'username' => $username,
            'name'     => $name,
            'password' => $hashedPassword,
            'role'     => $role,
            'image'    => $imageName,
        ]);
    }
}
