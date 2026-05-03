<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\UseCase;

use App\Modules\Product\Infrastructure\Repository\ProductRepository;
use InvalidArgumentException;

/**
 * Handles business logic for creating a new product.
 */
class CreateProductUseCase
{
    public function __construct(private readonly ProductRepository $repository) {}

    /**
     * Validates input and persists a new product.
     *
     * @return array<string, mixed> The created product row.
     * @throws InvalidArgumentException when validation fails.
     */
    public function execute(string $name, float $price, ?string $imageName): array
    {
        // Validate name.
        if (empty($name)) {
            throw new InvalidArgumentException('Product name is required.');
        }

        // Validate price.
        if ($price <= 0) {
            throw new InvalidArgumentException('Product price must be greater than zero.');
        }

        // Ensure name uniqueness.
        if ($this->repository->findByName($name) !== null) {
            throw new InvalidArgumentException('A product with this name already exists.');
        }

        return $this->repository->create([
            'name'  => $name,
            'price' => $price,
            'image' => $imageName,
        ]);
    }
}
