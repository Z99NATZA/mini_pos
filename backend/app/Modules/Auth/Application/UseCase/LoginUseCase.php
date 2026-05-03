<?php

declare(strict_types=1);

namespace App\Modules\Auth\Application\UseCase;

use App\Modules\Auth\Domain\Entity\User;
use App\Modules\Auth\Infrastructure\Repository\AuthRepository;
use Firebase\JWT\JWT;
use RuntimeException;

/**
 * Handles the user login flow: credential verification and JWT issuance.
 */
class LoginUseCase
{
    public function __construct(private readonly AuthRepository $repository) {}

    /**
     * Validates credentials, then returns a JWT token and user data.
     *
     * @return array{token: string, user: array<string, mixed>}
     * @throws RuntimeException when the credentials are invalid.
     */
    public function execute(string $username, string $password): array
    {
        $row = $this->repository->findByUsername($username);

        if ($row === null || !password_verify($password, $row['password'])) {
            throw new RuntimeException('Invalid username or password.');
        }

        $user   = User::fromArray($row);
        $secret = $_ENV['JWT_SECRET'] ?? '';
        $now    = time();

        $payload = [
            'sub'      => $user->id,
            'username' => $user->username,
            'name'     => $user->name,
            'role'     => $user->role,
            'iat'      => $now,
            'exp'      => $now + 86400, // 24 hours
        ];

        $token = JWT::encode($payload, $secret, 'HS256');

        return [
            'token' => $token,
            'user'  => $user->toArray(),
        ];
    }
}
