<?php

declare(strict_types=1);

namespace App\Modules\Size\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\Size\Infrastructure\Repository\SizeRepository;
use App\Shared\Helpers\Sanitizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Handles HTTP endpoints for the Size module.
 * Simple CRUD - business logic is minimal and handled directly here.
 */
class SizeController
{
    /**
     * GET /api/sizes?page=1&per_page=10
     *
     * @param array<string, mixed> $authUser
     */
    public function index(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $page    = max(1, Sanitizer::int($request->query->get('page', '1')));
            $perPage = max(1, min(100, Sanitizer::int($request->query->get('per_page', '10'))));

            $pdo        = Connection::getInstance();
            $repository = new SizeRepository($pdo);
            $result     = $repository->findAll($page, $perPage);

            return Response::paginate($result['items'], $result['total'], $page, $perPage);
        } catch (Throwable $e) {
            error_log('SizeController::index error: ' . $e->getMessage());
            return Response::error('Failed to retrieve sizes.', 500);
        }
    }

    /**
     * POST /api/sizes
     * Body: { "name": string, "price": numeric }
     *
     * @param array<string, mixed> $authUser
     */
    public function store(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $body  = json_decode($request->getContent(), true) ?? [];
            $name  = Sanitizer::string((string) ($body['name']  ?? ''));
            $price = Sanitizer::money((string) ($body['price'] ?? '0'));

            if (empty($name)) {
                return Response::error('Size name is required.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new SizeRepository($pdo);
            $size       = $repository->create(['name' => $name, 'price' => $price]);

            return Response::success('Size created successfully.', $size, 201);
        } catch (Throwable $e) {
            error_log('SizeController::store error: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'unique') ? 'A size with this name already exists.' : 'Failed to create size.';
            return Response::error($message, 422);
        }
    }

    /**
     * PUT /api/sizes/{id}
     * Body: { "name": string, "price": numeric }
     *
     * @param array<string, mixed> $authUser
     */
    public function update(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid size ID.', 422);
            }

            $body  = json_decode($request->getContent(), true) ?? [];
            $name  = Sanitizer::string((string) ($body['name']  ?? ''));
            $price = Sanitizer::money((string) ($body['price'] ?? '0'));

            if (empty($name)) {
                return Response::error('Size name is required.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new SizeRepository($pdo);
            $size       = $repository->update($id, ['name' => $name, 'price' => $price]);

            if ($size === false) {
                return Response::error('Size not found.', 404);
            }

            return Response::success('Size updated successfully.', $size);
        } catch (Throwable $e) {
            error_log('SizeController::update error: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'unique') ? 'A size with this name already exists.' : 'Failed to update size.';
            return Response::error($message, 422);
        }
    }

    /**
     * DELETE /api/sizes/{id}
     *
     * @param array<string, mixed> $authUser
     */
    public function destroy(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid size ID.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new SizeRepository($pdo);
            $repository->delete($id);

            return Response::success('Size deleted successfully.');
        } catch (Throwable $e) {
            error_log('SizeController::destroy error: ' . $e->getMessage());
            return Response::error('Failed to delete size.', 500);
        }
    }
}
