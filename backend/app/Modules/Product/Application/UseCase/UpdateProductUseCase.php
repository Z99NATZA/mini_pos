<?php

declare(strict_types=1);

namespace App\Modules\Product\Application\UseCase;

use App\Modules\Product\Infrastructure\Repository\ProductRepository;
use InvalidArgumentException;

/**
 * Handles business logic for updating an existing product.
 */
class UpdateProductUseCase
{
    public function __construct(private readonly ProductRepository $repository) {}

    /**
     * Validates input and updates the product.
     *
     * @return array<string, mixed> The updated product row.
     * @throws InvalidArgumentException when validation fails.
     */
    public function execute(int $id, string $name, float $price, ?string $imageName): array
    {
        // Validate name.
        if (empty($name)) {
            throw new InvalidArgumentException('Product name is required.');
        }

        // Validate price.
        if ($price <= 0) {
            throw new InvalidArgumentException('Product price must be greater than zero.');
        }

        // Ensure product exists.
        $existing = $this->repository->findById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Product not found.');
        }

        // Ensure name uniqueness excluding the current product.
        if ($this->repository->findByName($name, $id) !== null) {
            throw new InvalidArgumentException('Another product with this name already exists.');
        }

        // If no new image was uploaded, keep the existing image filename.
        $finalImage = $imageName ?? $existing['image'];

        return $this->repository->update($id, [
            'name'  => $name,
            'price' => $price,
            'image' => $finalImage,
        ]);
    }
}
