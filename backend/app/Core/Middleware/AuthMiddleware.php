<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Http\Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Validates the JWT Bearer token from the Authorization header.
 */
class AuthMiddleware
{
    /**
     * Authenticates the request by verifying the Bearer JWT token.
     * Returns the decoded token payload (user data) on success.
     * Returns a 401 JSON response on failure.
     *
     * @return array<string, mixed>|SymfonyResponse Returns user payload array on success,
     *                                              or a SymfonyResponse on failure.
     */
    public static function authenticate(Request $request): array|SymfonyResponse
    {
        $authHeader = $request->headers->get('Authorization', '');

        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return Response::error('Authorization token is required.', 401);
        }

        $token  = substr($authHeader, 7);
        $secret = $_ENV['JWT_SECRET'] ?? '';

        if (empty($secret)) {
            return Response::error('Server JWT configuration error.', 500);
        }

        try {
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            // Convert stdClass to array for uniform handling in controllers.
            return (array) $decoded;
        } catch (Throwable $e) {
            return Response::error('Invalid or expired token.', 401);
        }
    }
}
