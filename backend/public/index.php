<?php

declare(strict_types=1);

// Load Composer autoloader.
require_once __DIR__ . "/../vendor/autoload.php";

use App\Core\Database\Connection;
use App\Core\Init\DefaultUser;
use App\Core\Init\SchemaInit;
use App\Core\Middleware\CorsMiddleware;
use App\Core\Router\Router;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;

// Load environment variables from .env file.
$envFile = __DIR__ . "/../.env";

if (file_exists($envFile)) {
    $dotenv = new Dotenv();
    $dotenv->load($envFile);
}

// Establish the database connection (terminates with error if unreachable).
try {
    $pdo = Connection::getInstance();
} catch (Throwable $e) {
    http_response_code(503);
    header("Content-Type: application/json");
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed.",
    ]);
    exit();
}

// Apply database schema on first boot — CREATE TABLE IF NOT EXISTS is idempotent.
SchemaInit::ensure($pdo);

// Seed the default admin user on first boot (guarded by a lock file).
DefaultUser::ensure($pdo);

// Build the Symfony request from PHP globals.
$request = Request::createFromGlobals();

// Load route definitions.
$routes = require __DIR__ . "/../config/routes.php";

// Create the router and wrap dispatch in CORS middleware.
$router = new Router($routes);

$response = CorsMiddleware::handle($request, static function (
    Request $req,
) use ($router): \Symfony\Component\HttpFoundation\Response {
    return $router->dispatch($req);
});

// Send the HTTP response to the client.
$response->send();
