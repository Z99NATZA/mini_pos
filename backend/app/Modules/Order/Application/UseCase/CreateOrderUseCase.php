<?php

declare(strict_types=1);

namespace App\Modules\Order\Application\UseCase;

use App\Modules\Order\Infrastructure\Repository\OrderRepository;
use App\Shared\Helpers\Sanitizer;
use InvalidArgumentException;

/**
 * Handles business logic for creating a new sales order.
 */
class CreateOrderUseCase
{
    public function __construct(private readonly OrderRepository $repository) {}

    /**
     * Validates the order data, generates a sequential order number,
     * persists the order, and returns the full order record.
     *
     * @param array<int, array<string, mixed>> $items          Line items from the request.
     * @param float                            $receivedAmount Cash tendered by the customer.
     * @param string                           $cashierName    Name of the cashier processing the order.
     * @return array<string, mixed> The persisted order with all items and toppings.
     * @throws InvalidArgumentException when validation fails.
     */
    public function execute(array $items, float $receivedAmount, string $cashierName): array
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Order must contain at least one item.');
        }

        if (empty($cashierName)) {
            throw new InvalidArgumentException('Cashier name is required.');
        }

        // Calculate total from items. 
        $totalAmount = 0.0;

        foreach ($items as $item) {
            $amount       = Sanitizer::money((string) ($item['amount'] ?? 0));
            $totalAmount += $amount;
        }

        $totalAmount = round($totalAmount, 2);

        if ($receivedAmount < $totalAmount) {
            throw new InvalidArgumentException(
                sprintf(
                    'Received amount (%.2f) is less than total amount (%.2f).',
                    $receivedAmount,
                    $totalAmount
                )
            );
        }

        $changeAmount = round($receivedAmount - $totalAmount, 2);

        // Generate the order number: YYYYMMDD + 5-digit zero-padded sequence.
        $orderNumber = $this->generateOrderNumber();

        // Persist order header.
        $orderId = $this->repository->createOrder([
            'order_number'    => $orderNumber,
            'cashier_name'    => $cashierName,
            'total_amount'    => $totalAmount,
            'received_amount' => $receivedAmount,
            'change_amount'   => $changeAmount,
        ]);

        // Persist each line item and its toppings.
        foreach ($items as $index => $item) {
            $product  = $item['product']  ?? [];
            $size     = $item['size']     ?? [];
            $type     = $item['type']     ?? [];
            $toppings = $item['toppings'] ?? [];

            // Unique code scoped to this order for linking toppings to items.
            $itemCode = $orderNumber . '_' . sprintf('%03d', $index + 1);

            $this->repository->createOrderItem([
                'order_id'        => $orderId,
                'order_item_code' => $itemCode,
                'product_name'    => Sanitizer::string((string) ($product['name']  ?? '')),
                'product_price'   => Sanitizer::money((string) ($product['price'] ?? 0)),
                'size_name'       => Sanitizer::string((string) ($size['name']    ?? '')),
                'size_price'      => Sanitizer::money((string) ($size['price']    ?? 0)),
                'type_name'       => Sanitizer::string((string) ($type['name']    ?? '')),
                'type_price'      => Sanitizer::money((string) ($type['price']    ?? 0)),
                'quantity'        => max(1, (int) ($product['qty']               ?? 1)),
                'amount'          => Sanitizer::money((string) ($item['amount']   ?? 0)),
            ]);

            // Persist each topping for this item.
            foreach ($toppings as $topping) {
                $this->repository->createOrderItemTopping([
                    'order_id'        => $orderId,
                    'order_item_code' => $itemCode,
                    'topping_name'    => Sanitizer::string((string) ($topping['name']  ?? '')),
                    'topping_price'   => Sanitizer::money((string) ($topping['price'] ?? 0)),
                ]);
            }
        }

        // Return the complete, freshly persisted order.
        $order = $this->repository->findById($orderId);

        if ($order === null) {
            throw new \RuntimeException('Failed to retrieve the newly created order.');
        }

        return $order;
    }

    /**
     * Generates a unique, sequential order number for today.
     * Format: YYYYMMDD + 5-digit zero-padded sequence (e.g. 2025061500001).
     */
    private function generateOrderNumber(): string
    {
        $today       = date('Ymd');
        $lastNumber  = $this->repository->getLastOrderNumberToday();
        $sequence    = 1;

        if ($lastNumber !== null && strlen($lastNumber) >= 13) {
            // Extract the 5-digit sequence portion from the end.
            $lastSeq  = (int) substr($lastNumber, 8, 5);
            $sequence = $lastSeq + 1;
        }

        return $today . sprintf('%05d', $sequence);
    }
}
