<?php

declare(strict_types=1);

namespace App\Modules\Order\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\Order\Application\UseCase\CreateOrderUseCase;
use App\Modules\Order\Infrastructure\Repository\OrderRepository;
use App\Shared\Helpers\Sanitizer;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Handles HTTP endpoints for the Order module.
 */
class OrderController
{
    /**
     * GET /api/orders?page=1&per_page=10
     *
     * @param array<string, mixed> $authUser
     */
    public function index(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $page    = max(1, Sanitizer::int($request->query->get('page', '1')));
            $perPage = max(1, min(100, Sanitizer::int($request->query->get('per_page', '10'))));

            $pdo        = Connection::getInstance();
            $repository = new OrderRepository($pdo);
            $result     = $repository->findAll($page, $perPage);

            return Response::paginate($result['items'], $result['total'], $page, $perPage);
        } catch (Throwable $e) {
            error_log('OrderController::index error: ' . $e->getMessage());
            return Response::error('Failed to retrieve orders.', 500);
        }
    }

    /**
     * POST /api/orders
     * Body: { items: [...], received_amount: float, cashier_name: string }
     *
     * @param array<string, mixed> $authUser
     */
    public function store(Request $request, array $authUser = []): SymfonyResponse
    {
        try {
            $body = json_decode($request->getContent(), true);

            if (!is_array($body)) {
                return Response::error('Invalid JSON body.', 400);
            }

            $items          = is_array($body['items'] ?? null) ? $body['items'] : [];
            $receivedAmount = Sanitizer::money((string) ($body['received_amount'] ?? '0'));
            $cashierName    = Sanitizer::string((string) ($body['cashier_name']   ?? ''));

            $pdo        = Connection::getInstance();
            $repository = new OrderRepository($pdo);
            $useCase    = new CreateOrderUseCase($repository);

            $order = $useCase->execute($items, $receivedAmount, $cashierName);

            return Response::success('Order created successfully.', $order, 201);
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log('OrderController::store error: ' . $e->getMessage());
            return Response::error('Failed to create order.', 500);
        }
    }

    /**
     * GET /api/orders/{id}
     * Returns the full order receipt with items and toppings.
     *
     * @param array<string, mixed> $authUser
     */
    public function show(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid order ID.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new OrderRepository($pdo);
            $order      = $repository->findById($id);

            if ($order === null) {
                return Response::error('Order not found.', 404);
            }

            return Response::success('Order retrieved.', $order);
        } catch (Throwable $e) {
            error_log('OrderController::show error: ' . $e->getMessage());
            return Response::error('Failed to retrieve order.', 500);
        }
    }

    /**
     * DELETE /api/orders/{id}
     * Cascade deletes the order with all its items and toppings.
     *
     * @param array<string, mixed> $authUser
     */
    public function destroy(Request $request, array $authUser = [], int $id = 0): SymfonyResponse
    {
        try {
            if ($id <= 0) {
                return Response::error('Invalid order ID.', 422);
            }

            $pdo        = Connection::getInstance();
            $repository = new OrderRepository($pdo);
            $order      = $repository->findById($id);

            if ($order === null) {
                return Response::error('Order not found.', 404);
            }

            $repository->deleteOrder($id);

            return Response::success('Order deleted successfully.');
        } catch (Throwable $e) {
            error_log('OrderController::destroy error: ' . $e->getMessage());
            return Response::error('Failed to delete order.', 500);
        }
    }
}
