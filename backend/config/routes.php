<?php

declare(strict_types=1);

use App\Modules\Auth\Transport\HTTP\Controllers\AuthController;
use App\Modules\Dashboard\Transport\HTTP\Controllers\DashboardController;
use App\Modules\Order\Transport\HTTP\Controllers\OrderController;
use App\Modules\Product\Transport\HTTP\Controllers\ProductController;
use App\Modules\Size\Transport\HTTP\Controllers\SizeController;
use App\Modules\Topping\Transport\HTTP\Controllers\ToppingController;
use App\Modules\Type\Transport\HTTP\Controllers\TypeController;
use App\Modules\User\Transport\HTTP\Controllers\UserController;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

$routes = new RouteCollection();

// Auth
$routes->add('auth.login', new Route('/api/auth/login', ['_controller' => [AuthController::class, 'login']], methods: ['POST']));
$routes->add('auth.me', new Route('/api/auth/me', ['_controller' => [AuthController::class, 'me'], '_auth' => true], methods: ['GET']));

// Dashboard
$routes->add('dashboard.index', new Route('/api/dashboard', ['_controller' => [DashboardController::class, 'index'], '_auth' => true], methods: ['GET']));

// Products
$routes->add('products.index',   new Route('/api/products',      ['_controller' => [ProductController::class, 'index'],   '_auth' => true], methods: ['GET']));
$routes->add('products.store',   new Route('/api/products',      ['_controller' => [ProductController::class, 'store'],   '_auth' => true], methods: ['POST']));
$routes->add('products.update',  new Route('/api/products/{id}', ['_controller' => [ProductController::class, 'update'],  '_auth' => true], requirements: ['id' => '\d+'], methods: ['PUT', 'POST']));
$routes->add('products.destroy', new Route('/api/products/{id}', ['_controller' => [ProductController::class, 'destroy'], '_auth' => true], requirements: ['id' => '\d+'], methods: ['DELETE']));

// Sizes
$routes->add('sizes.index',   new Route('/api/sizes',      ['_controller' => [SizeController::class, 'index'],   '_auth' => true], methods: ['GET']));
$routes->add('sizes.store',   new Route('/api/sizes',      ['_controller' => [SizeController::class, 'store'],   '_auth' => true], methods: ['POST']));
$routes->add('sizes.update',  new Route('/api/sizes/{id}', ['_controller' => [SizeController::class, 'update'],  '_auth' => true], requirements: ['id' => '\d+'], methods: ['PUT']));
$routes->add('sizes.destroy', new Route('/api/sizes/{id}', ['_controller' => [SizeController::class, 'destroy'], '_auth' => true], requirements: ['id' => '\d+'], methods: ['DELETE']));

// Types
$routes->add('types.index',   new Route('/api/types',      ['_controller' => [TypeController::class, 'index'],   '_auth' => true], methods: ['GET']));
$routes->add('types.store',   new Route('/api/types',      ['_controller' => [TypeController::class, 'store'],   '_auth' => true], methods: ['POST']));
$routes->add('types.update',  new Route('/api/types/{id}', ['_controller' => [TypeController::class, 'update'],  '_auth' => true], requirements: ['id' => '\d+'], methods: ['PUT']));
$routes->add('types.destroy', new Route('/api/types/{id}', ['_controller' => [TypeController::class, 'destroy'], '_auth' => true], requirements: ['id' => '\d+'], methods: ['DELETE']));

// Toppings
$routes->add('toppings.index',   new Route('/api/toppings',      ['_controller' => [ToppingController::class, 'index'],   '_auth' => true], methods: ['GET']));
$routes->add('toppings.store',   new Route('/api/toppings',      ['_controller' => [ToppingController::class, 'store'],   '_auth' => true], methods: ['POST']));
$routes->add('toppings.update',  new Route('/api/toppings/{id}', ['_controller' => [ToppingController::class, 'update'],  '_auth' => true], requirements: ['id' => '\d+'], methods: ['PUT']));
$routes->add('toppings.destroy', new Route('/api/toppings/{id}', ['_controller' => [ToppingController::class, 'destroy'], '_auth' => true], requirements: ['id' => '\d+'], methods: ['DELETE']));

// Orders
$routes->add('orders.index',   new Route('/api/orders',      ['_controller' => [OrderController::class, 'index'],   '_auth' => true], methods: ['GET']));
$routes->add('orders.store',   new Route('/api/orders',      ['_controller' => [OrderController::class, 'store'],   '_auth' => true], methods: ['POST']));
$routes->add('orders.show',    new Route('/api/orders/{id}', ['_controller' => [OrderController::class, 'show'],    '_auth' => true], requirements: ['id' => '\d+'], methods: ['GET']));
$routes->add('orders.destroy', new Route('/api/orders/{id}', ['_controller' => [OrderController::class, 'destroy'], '_auth' => true], requirements: ['id' => '\d+'], methods: ['DELETE']));

// Users
$routes->add('users.index',   new Route('/api/users',      ['_controller' => [UserController::class, 'index'],   '_auth' => true], methods: ['GET']));
$routes->add('users.store',   new Route('/api/users',      ['_controller' => [UserController::class, 'store'],   '_auth' => true], methods: ['POST']));
$routes->add('users.update',  new Route('/api/users/{id}', ['_controller' => [UserController::class, 'update'],  '_auth' => true], requirements: ['id' => '\d+'], methods: ['PUT', 'POST']));
$routes->add('users.destroy', new Route('/api/users/{id}', ['_controller' => [UserController::class, 'destroy'], '_auth' => true], requirements: ['id' => '\d+'], methods: ['DELETE']));

return $routes;
