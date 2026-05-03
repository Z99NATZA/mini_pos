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
use App\Shared\Helpers\SupabaseStorage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class ProductController
{
    private const LOCAL_UPLOAD_DIR =
        __DIR__ . "/../../../../../../public/uploads/products/";
    private const BUCKET = "mini-pos-images";
    private const FOLDER = "products";
    private const ALLOWED_MIMES = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
    ];
    private const MAX_UPLOAD_BYTES = 12 * 1024 * 1024;

    /** @param array<string, mixed> $authUser */
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

    /** @param array<string, mixed> $authUser */
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

            $imageValue = $this->handleUpload();

            $pdo = Connection::getInstance();
            $repository = new ProductRepository($pdo);
            $useCase = new CreateProductUseCase($repository);
            $product = $useCase->execute($name, $price, $imageValue);

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

    /** @param array<string, mixed> $authUser */
    public function update(
        Request $request,
        array $authUser = [],
        int $id = 0,
    ): SymfonyResponse {
        try {
            if ($id <= 0) {
                return Response::error("Invalid product ID.", 422);
            }

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

            $imageValue = $this->handleUpload();

            $pdo = Connection::getInstance();
            $repository = new ProductRepository($pdo);
            $useCase = new UpdateProductUseCase($repository);
            $product = $useCase->execute($id, $name, $price, $imageValue);

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

    /** @param array<string, mixed> $authUser */
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
            $this->deleteImage($product["image"] ?? null);

            return Response::success("Product deleted successfully.");
        } catch (Throwable $e) {
            error_log("ProductController::destroy error: " . $e->getMessage());
            return Response::error("Failed to delete product.", 500);
        }
    }

    /** @param array<string, mixed> $product */
    private function withImageUrl(array $product): array
    {
        $product["image"] = ImageUrl::product($product["image"] ?? null);
        return $product;
    }

    /**
     * Processes the uploaded 'image' file.
     * Uses Supabase Storage when configured, local filesystem otherwise.
     * Returns a full URL (Supabase) or a bare filename (local).
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

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file["tmp_name"]);

        if (!in_array($mimeType, self::ALLOWED_MIMES, true)) {
            throw new InvalidArgumentException(
                "Only JPEG, PNG, GIF, and WebP images are allowed.",
            );
        }

        $extension = match ($mimeType) {
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/webp" => "webp",
            default => "jpg",
        };

        $filename = uniqid("product_", true) . "." . $extension;

        // — Supabase Storage (production) —
        if (SupabaseStorage::isConfigured()) {
            $path = self::FOLDER . "/" . $filename;
            return SupabaseStorage::upload(
                self::BUCKET,
                $path,
                $file["tmp_name"],
                $mimeType,
            );
        }

        // — Local filesystem (development) —
        if (!is_dir(self::LOCAL_UPLOAD_DIR)) {
            mkdir(self::LOCAL_UPLOAD_DIR, 0755, true);
        }

        if (
            !move_uploaded_file(
                $file["tmp_name"],
                self::LOCAL_UPLOAD_DIR . $filename,
            )
        ) {
            throw new InvalidArgumentException(
                "Failed to save the uploaded image.",
            );
        }

        return $filename;
    }

    /**
     * Deletes the image from whichever backend stored it.
     */
    private function deleteImage(?string $value): void
    {
        if (empty($value)) {
            return;
        }

        if (
            str_starts_with($value, "http") &&
            SupabaseStorage::isConfigured()
        ) {
            // Extract "products/filename.ext" from the full Supabase URL.
            $prefix = "/storage/v1/object/public/" . self::BUCKET . "/";
            $parsed = parse_url($value, PHP_URL_PATH) ?? "";
            if (str_starts_with($parsed, $prefix)) {
                SupabaseStorage::delete(
                    self::BUCKET,
                    substr($parsed, strlen($prefix)),
                );
            }
            return;
        }

        // Local file.
        $filePath = self::LOCAL_UPLOAD_DIR . $value;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
