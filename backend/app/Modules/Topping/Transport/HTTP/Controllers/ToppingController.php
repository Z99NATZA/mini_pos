<?php

declare(strict_types=1);

namespace App\Modules\Topping\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\Topping\Infrastructure\Repository\ToppingRepository;
use App\Shared\Helpers\Sanitizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Handles HTTP endpoints for the Topping module.
 * Simple CRUD - business logic is minimal and handled directly here.
 */
class ToppingController
{
    /**
     * GET /api/toppings?page=1&per_page=10
     *
     * @param array<string, mixed> $authUser
     */
    public function index(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $page    = max(1, Sanitizer::int($request->query->get('page', '1')));
            $perPage = max(1, min(100, Sanitizer::int($request->query->get('per_page', '10'))));

            $pdo        = Connection::getInstance();
            $repository = new ToppingRepository($pdo);
            $result     = $repository->findAll($page, $perPage);

            return Response::paginate($result['items'], $result['total'], $page, $perPage);
        } catch (Throwable $e) {
            error_log('ToppingController::index error: ' . $e->getMessage());
            return Response::error('Failed to retrieve toppings.', 500);
        }
    }

    /**
     * POST /api/toppings
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
                return Response::error('Topping name is required.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new ToppingRepository($pdo);
            $topping    = $repository->create(['name' => $name, 'price' => $price]);

            return Response::success('Topping created successfully.', $topping, 201);
        } catch (Throwable $e) {
            error_log('ToppingController::store error: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'unique') ? 'A topping with this name already exists.' : 'Failed to create topping.';
            return Response::error($message, 422);
        }
    }

    /**
     * PUT /api/toppings/{id}
     * Body: { "name": string, "price": numeric }
     *
     * @param array<string, mixed> $authUser
     */
    public function update(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid topping ID.', 422);
            }

            $body  = json_decode($request->getContent(), true) ?? [];
            $name  = Sanitizer::string((string) ($body['name']  ?? ''));
            $price = Sanitizer::money((string) ($body['price'] ?? '0'));

            if (empty($name)) {
                return Response::error('Topping name is required.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new ToppingRepository($pdo);
            $topping    = $repository->update($id, ['name' => $name, 'price' => $price]);

            if ($topping === false) {
                return Response::error('Topping not found.', 404);
            }

            return Response::success('Topping updated successfully.', $topping);
        } catch (Throwable $e) {
            error_log('ToppingController::update error: ' . $e->getMessage());
            $message = str_contains($e->getMessage(), 'unique') ? 'A topping with this name already exists.' : 'Failed to update topping.';
            return Response::error($message, 422);
        }
    }

    /**
     * DELETE /api/toppings/{id}
     *
     * @param array<string, mixed> $authUser
     */
    public function destroy(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid topping ID.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new ToppingRepository($pdo);
            $repository->delete($id);

            return Response::success('Topping deleted successfully.');
        } catch (Throwable $e) {
            error_log('ToppingController::destroy error: ' . $e->getMessage());
            return Response::error('Failed to delete topping.', 500);
        }
    }
}
