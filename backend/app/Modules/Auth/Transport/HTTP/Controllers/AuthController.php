<?php

declare(strict_types=1);

namespace App\Modules\Auth\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\Auth\Application\UseCase\LoginUseCase;
use App\Modules\Auth\Infrastructure\Repository\AuthRepository;
use App\Shared\Helpers\ImageUrl;
use App\Shared\Helpers\Sanitizer;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Handles authentication-related HTTP endpoints.
 */
class AuthController
{
    /**
     * POST /api/auth/login
     * Body: { "username": string, "password": string }
     *
     * @param array<string, mixed> $authUser Unused for this public endpoint.
     */
    public function login(
        Request $request,
        array $authUser = [],
    ): SymfonyResponse {
        try {
            $body = json_decode($request->getContent(), true) ?? [];

            $username = Sanitizer::string((string) ($body["username"] ?? ""));
            $password = (string) ($body["password"] ?? "");

            if (empty($username) || empty($password)) {
                return Response::error(
                    "Username and password are required.",
                    422,
                );
            }

            $pdo = Connection::getInstance();
            $repository = new AuthRepository($pdo);
            $useCase = new LoginUseCase($repository);

            $result = $useCase->execute($username, $password);

            $result["user"]["image"] = ImageUrl::user(
                $result["user"]["image"] ?? null,
            );

            return Response::success("Login successful.", $result);
        } catch (RuntimeException $e) {
            return Response::error($e->getMessage(), 401);
        } catch (Throwable $e) {
            error_log("AuthController::login error: " . $e->getMessage());
            return Response::error("An unexpected error occurred.", 500);
        }
    }

    /**
     * GET /api/auth/me
     * Returns the currently authenticated user's profile.
     *
     * @param array<string, mixed> $authUser Decoded JWT payload injected by AuthMiddleware.
     */
    public function me(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $pdo = Connection::getInstance();
            $stmt = $pdo->prepare(
                "SELECT id, username, name, role, image FROM users WHERE id = :id LIMIT 1",
            );
            $stmt->execute([":id" => (int) ($authUser["sub"] ?? 0)]);
            $user = $stmt->fetch();

            if (!$user) {
                return Response::error("User not found.", 404);
            }

            $user["image"] = ImageUrl::user($user["image"] ?? null);

            return Response::success("User retrieved.", $user);
        } catch (Throwable $e) {
            error_log("AuthController::me error: " . $e->getMessage());
            return Response::error("An unexpected error occurred.", 500);
        }
    }
}
