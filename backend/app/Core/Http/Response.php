<?php

declare(strict_types=1);

namespace App\Core\Http;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Static factory for consistent JSON API responses.
 */
class Response
{
    /**
     * Returns a raw JSON response with the given data and status code.
     */
    public static function json(mixed $data, int $status = 200): SymfonyResponse
    {
        return new JsonResponse($data, $status);
    }

    /**
     * Returns a JSON error response.
     *
     * @param array<string, mixed> $errors Optional field-level error details.
     */
    public static function error(string $message, int $status = 400, array $errors = []): SymfonyResponse
    {
        $body = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $body['errors'] = $errors;
        }

        return new JsonResponse($body, $status);
    }

    /**
     * Returns a JSON success response.
     */
    public static function success(string $message, mixed $data = null, int $status = 200): SymfonyResponse
    {
        $body = [
            'success' => true,
            'message' => $message,
        ];

        if ($data !== null) {
            $body['data'] = $data;
        }

        return new JsonResponse($body, $status);
    }

    /**
     * Returns a paginated JSON success response.
     *
     * @param array<int, mixed> $items The current page of items.
     */
    public static function paginate(array $items, int $total, int $page, int $perPage): SymfonyResponse
    {
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        $body = [
            'success'    => true,
            'data'       => $items,
            'pagination' => [
                'page'        => $page,
                'per_page'    => $perPage,
                'total'       => $total,
                'total_pages' => $totalPages,
            ],
        ];

        return new JsonResponse($body, 200);
    }
}
