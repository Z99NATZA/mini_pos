<?php

declare(strict_types=1);

namespace App\Modules\Product\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\Product\Application\UseCase\CreateProductUseCase;
use App\Modules\Product\Application\UseCase\UpdateProductUseCase;
use App\Modules\Product\Infrastructure\Repository\ProductRepository;
use App\Shared\Helpers\ImageUrl;
use App\Shared\Helpers\Sanitizer;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

/**
 * Handles HTTP endpoints for the Product module.
 */
class ProductController
{
    private const UPLOAD_DIR =
        __DIR__ . "/../../../../../../public/uploads/products/";
    private const ALLOWED_MIMES = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
    ];
    private const MAX_UPLOAD_BYTES = 12 * 1024 * 1024; // 12 MB

    /**
     * GET /api/products?page=1&per_page=10&search=
     *
     * @param array<string, mixed> $authUser
     */
    public function index(
        Request $request,
        array $authUser = [],
    ): SymfonyResponse {
        try {
            $page = max(1, Sanitizer::int($request->query->get("page", "1")));
            $perPage = max(
                1,
                min(
                    100,
                    Sanitizer::int($request->query->get("per_page", "10")),
                ),
            );
            $search = Sanitizer::string(
                (string) $request->query->get("search", ""),
            );

            $pdo = Connection::getInstance();
            $repository = new ProductRepository($pdo);
            $result = $repository->findAll($page, $perPage, $search);

            $items = array_map([$this, "withImageUrl"], $result["items"]);

            return Response::paginate(
                $items,
                $result["total"],
                $page,
                $perPage,
            );
        } catch (Throwable $e) {
            error_log("ProductController::index error: " . $e->getMessage());
            return Response::error("Failed to retrieve products.", 500);
        }
    }

    /**
     * POST /api/products
     * Accepts multipart/form-data with an optional image file.
     *
     * @param array<string, mixed> $authUser
     */
    public function store(
        Request $request,
        array $authUser = [],
    ): SymfonyResponse {
        try {
            $name = Sanitizer::string(
                (string) $request->request->get("name", ""),
            );
            $price = Sanitizer::money(
                (string) $request->request->get("price", "0"),
            );

            $imageName = $this->handleUpload();

            $pdo = Connection::getInstance();
            $repository = new ProductRepository($pdo);
            $useCase = new CreateProductUseCase($repository);

            $product = $useCase->execute($name, $price, $imageName);

            return Response::success(
                "Product created successfully.",
                $this->withImageUrl($product),
                201,
            );
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log("ProductController::store error: " . $e->getMessage());
            return Response::error("Failed to create product.", 500);
        }
    }

    /**
     * PUT /api/products/{id}
     * Accepts multipart/form-data or JSON body with an optional image file.
     *
     * @param array<string, mixed> $authUser
     */
    public function update(
        Request $request,
        array $authUser = [],
        int $id = 0,
    ): SymfonyResponse {
        try {
            if ($id <= 0) {
                return Response::error("Invalid product ID.", 422);
            }

            // Support both JSON body and multipart form data.
            $contentType = $request->headers->get("Content-Type", "");

            if (str_contains($contentType, "application/json")) {
                $body = json_decode($request->getContent(), true) ?? [];
                $name = Sanitizer::string((string) ($body["name"] ?? ""));
                $price = Sanitizer::money((string) ($body["price"] ?? "0"));
            } else {
                $name = Sanitizer::string(
                    (string) $request->request->get("name", ""),
                );
                $price = Sanitizer::money(
                    (string) $request->request->get("price", "0"),
                );
            }

            $imageName = $this->handleUpload();

            $pdo = Connection::getInstance();
            $repository = new ProductRepository($pdo);
            $useCase = new UpdateProductUseCase($repository);

            $product = $useCase->execute($id, $name, $price, $imageName);

            return Response::success(
                "Product updated successfully.",
                $this->withImageUrl($product),
            );
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log("ProductController::update error: " . $e->getMessage());
            return Response::error("Failed to update product.", 500);
        }
    }

    /**
     * DELETE /api/products/{id}
     *
     * @param array<string, mixed> $authUser
     */
    public function destroy(
        Request $request,
        array $authUser = [],
        int $id = 0,
    ): SymfonyResponse {
        try {
            if ($id <= 0) {
                return Response::error("Invalid product ID.", 422);
            }

            $pdo = Connection::getInstance();
            $repository = new ProductRepository($pdo);

            $product = $repository->findById($id);
            if ($product === null) {
                return Response::error("Product not found.", 404);
            }

            $repository->delete($id);

            // Remove the associated image file if one exists.
            if (!empty($product["image"])) {
                $filePath = self::UPLOAD_DIR . $product["image"];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            return Response::success("Product deleted successfully.");
        } catch (Throwable $e) {
            error_log("ProductController::destroy error: " . $e->getMessage());
            return Response::error("Failed to delete product.", 500);
        }
    }

    /**
     * Transforms the raw `image` filename in a product array into a full relative URL.
     *
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function withImageUrl(array $product): array
    {
        $product["image"] = ImageUrl::product($product["image"] ?? null);
        return $product;
    }

    /**
     * Processes the uploaded 'image' file from $_FILES.
     * Returns the saved filename on success, or null if no file was uploaded.
     *
     * @throws InvalidArgumentException when the upload is invalid.
     */
    private function handleUpload(): ?string
    {
        if (
            empty($_FILES["image"]) ||
            $_FILES["image"]["error"] === UPLOAD_ERR_NO_FILE
        ) {
            return null;
        }

        $file = $_FILES["image"];

        if ($file["error"] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException(
                "File upload error (code " . $file["error"] . ").",
            );
        }

        if ($file["size"] > self::MAX_UPLOAD_BYTES) {
            throw new InvalidArgumentException(
                "Image file exceeds the maximum allowed size of 12 MB.",
            );
        }

        // Use finfo for reliable MIME detection (not the client-supplied type).
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);

        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException(
                "Only JPEG, PNG, GIF, and WebP images are allowed.",
            );
        }

        if (!is_dir(self::UPLOAD_DIR)) {
            mkdir(self::UPLOAD_DIR, 0755, true);
        }

        $extension = match ($mimeType) {
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/webp" => "webp",
            default => "jpg",
        };

        $filename = uniqid("product_", true) . "." . $extension;
        $destination = self::UPLOAD_DIR . $filename;

        if (!move_uploaded_file($file["tmp_name"], $destination)) {
            throw new InvalidArgumentException(
                "Failed to save the uploaded image.",
            );
        }

        return $filename;
    }
}
