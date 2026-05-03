<?php

declare(strict_types=1);

namespace App\Modules\Type\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\Type\Infrastructure\Repository\TypeRepository;
use App\Shared\Helpers\Sanitizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Handles HTTP endpoints for the Type module.
 * Simple CRUD - business logic is minimal and handled directly here.
 */
class TypeController
{
    /**
     * GET /api/types?page=1&per_page=10
     *
     * @param array<string, mixed> $authUser
     */
    public function index(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $page    = max(1, Sanitizer::int($request->query->get('page', '1')));
            $perPage = max(1, min(100, Sanitizer::int($request->query->get('per_page', '10'))));

            $pdo        = Connection::getInstance();
            $repository = new TypeRepository($pdo);
            $result     = $repository->findAll($page, $perPage);

            return Response::paginate($result['items'], $result['total'], $page, $perPage);
        } catch (Throwable $e) {
            error_log('TypeController::index error: ' . $e->getMessage());
            return Response::error('Failed to retrieve types.', 500);
        }
    }

    /**
     * POST /api/types
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
                return Response::error('Type name is required.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new TypeRepository($pdo);
            $type       = $repository->create(['name' => $name, 'price' => $price]);

            return Response::success('Type created successfully.', $type, 201);
        } catch (Throwable $e) {
            error_log('TypeController::store error: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'unique') ? 'A type with this name already exists.' : 'Failed to create type.';
            return Response::error($message, 422);
        }
    }

    /**
     * PUT /api/types/{id}
     * Body: { "name": string, "price": numeric }
     *
     * @param array<string, mixed> $authUser
     */
    public function update(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid type ID.', 422);
            }

            $body  = json_decode($request->getContent(), true) ?? [];
            $name  = Sanitizer::string((string) ($body['name']  ?? ''));
            $price = Sanitizer::money((string) ($body['price'] ?? '0'));

            if (empty($name)) {
                return Response::error('Type name is required.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new TypeRepository($pdo);
            $type       = $repository->update($id, ['name' => $name, 'price' => $price]);

            if ($type === false) {
                return Response::error('Type not found.', 404);
            }

            return Response::success('Type updated successfully.', $type);
        } catch (Throwable $e) {
            error_log('TypeController::update error: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'unique') ? 'A type with this name already exists.' : 'Failed to update type.';
            return Response::error($message, 422);
        }
    }

    /**
     * DELETE /api/types/{id}
     *
     * @param array<string, mixed> $authUser
     */
    public function destroy(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid type ID.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new TypeRepository($pdo);
            $repository->delete($id);

            return Response::success('Type deleted successfully.');
        } catch (Throwable $e) {
            error_log('TypeController::destroy error: ' . $e->getMessage());
            return Response::error('Failed to delete type.', 500);
        }
    }
}
