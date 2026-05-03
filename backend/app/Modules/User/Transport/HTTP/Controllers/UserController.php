<?php

declare(strict_types=1);

namespace App\Modules\User\Transport\HTTP\Controllers;

use App\Core\Database\Connection;
use App\Core\Http\Response;
use App\Modules\User\Application\UseCase\CreateUserUseCase;
use App\Modules\User\Application\UseCase\UpdateUserUseCase;
use App\Modules\User\Infrastructure\Repository\UserRepository;
use App\Shared\Helpers\ImageUrl;
use App\Shared\Helpers\Sanitizer;
use App\Shared\Helpers\SupabaseStorage;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class UserController
{
    private const LOCAL_UPLOAD_DIR =
        __DIR__ . "/../../../../../../public/uploads/users/";
    private const BUCKET = "mini-pos-images";
    private const FOLDER = "users";
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

            $pdo = Connection::getInstance();
            $repository = new UserRepository($pdo);
            $result = $repository->findAll($page, $perPage);
            $items = array_map([$this, "withImageUrl"], $result["items"]);

            return Response::paginate(
                $items,
                $result["total"],
                $page,
                $perPage,
            );
        } catch (Throwable $e) {
            error_log("UserController::index error: " . $e->getMessage());
            return Response::error("Failed to retrieve users.", 500);
        }
    }

    /** @param array<string, mixed> $authUser */
    public function store(
        Request $request,
        array $authUser = [],
    ): SymfonyResponse {
        try {
            $username = Sanitizer::string(
                (string) $request->request->get("username", ""),
            );
            $name = Sanitizer::string(
                (string) $request->request->get("name", ""),
            );
            $password = (string) $request->request->get("password", "");
            $role = Sanitizer::string(
                (string) $request->request->get("role", "staff"),
            );

            if (empty($username)) {
                $body = json_decode($request->getContent(), true) ?? [];
                $username = Sanitizer::string(
                    (string) ($body["username"] ?? ""),
                );
                $name = Sanitizer::string((string) ($body["name"] ?? ""));
                $password = (string) ($body["password"] ?? "");
                $role = Sanitizer::string((string) ($body["role"] ?? "staff"));
            }

            $imageValue = $this->handleUpload();

            $pdo = Connection::getInstance();
            $repository = new UserRepository($pdo);
            $useCase = new CreateUserUseCase($repository);
            $user = $useCase->execute(
                $username,
                $name,
                $password,
                $role,
                $imageValue,
            );

            return Response::success(
                "User created successfully.",
                $this->withImageUrl($user),
                201,
            );
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log("UserController::store error: " . $e->getMessage());
            return Response::error("Failed to create user.", 500);
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
                return Response::error("Invalid user ID.", 422);
            }

            $contentType = $request->headers->get("Content-Type", "");

            if (str_contains($contentType, "application/json")) {
                $body = json_decode($request->getContent(), true) ?? [];
                $username = Sanitizer::string(
                    (string) ($body["username"] ?? ""),
                );
                $name = Sanitizer::string((string) ($body["name"] ?? ""));
                $password = (string) ($body["password"] ?? "");
                $role = Sanitizer::string((string) ($body["role"] ?? "staff"));
            } else {
                $username = Sanitizer::string(
                    (string) $request->request->get("username", ""),
                );
                $name = Sanitizer::string(
                    (string) $request->request->get("name", ""),
                );
                $password = (string) $request->request->get("password", "");
                $role = Sanitizer::string(
                    (string) $request->request->get("role", "staff"),
                );
            }

            $imageValue = $this->handleUpload();

            $pdo = Connection::getInstance();
            $repository = new UserRepository($pdo);
            $useCase = new UpdateUserUseCase($repository);
            $user = $useCase->execute(
                $id,
                $username,
                $name,
                $password,
                $role,
                $imageValue,
            );

            return Response::success(
                "User updated successfully.",
                $this->withImageUrl($user),
            );
        } catch (InvalidArgumentException $e) {
            return Response::error($e->getMessage(), 422);
        } catch (Throwable $e) {
            error_log("UserController::update error: " . $e->getMessage());
            return Response::error("Failed to update user.", 500);
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
                return Response::error("Invalid user ID.", 422);
            }

            if ($id === 1) {
                return Response::error(
                    "The default admin user cannot be deleted.",
                    403,
                );
            }

            $pdo = Connection::getInstance();
            $repository = new UserRepository($pdo);
            $user = $repository->findById($id);

            if ($user === null) {
                return Response::error("User not found.", 404);
            }

            if ($user["role"] === "admin" && $repository->countAdmins() <= 1) {
                return Response::error(
                    "Cannot delete the last admin user.",
                    403,
                );
            }

            $repository->delete($id);
            $this->deleteImage($user["image"] ?? null);

            return Response::success("User deleted successfully.");
        } catch (Throwable $e) {
            error_log("UserController::destroy error: " . $e->getMessage());
            return Response::error("Failed to delete user.", 500);
        }
    }

    /** @param array<string, mixed> $user */
    private function withImageUrl(array $user): array
    {
        $user["image"] = ImageUrl::user($user["image"] ?? null);
        return $user;
    }

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

        $filename = uniqid("user_", true) . "." . $extension;

        if (SupabaseStorage::isConfigured()) {
            $path = self::FOLDER . "/" . $filename;
            return SupabaseStorage::upload(
                self::BUCKET,
                $path,
                $file["tmp_name"],
                $mimeType,
            );
        }

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

    private function deleteImage(?string $value): void
    {
        if (empty($value)) {
            return;
        }

        if (
            str_starts_with($value, "http") &&
            SupabaseStorage::isConfigured()
        ) {
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

        $filePath = self::LOCAL_UPLOAD_DIR . $value;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
}
