<?php

declare(strict_types=1);

namespace App\Core\Router;

use App\Core\Http\Response;
use App\Core\Middleware\AuthMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;

/**
 * Dispatches incoming requests to the appropriate controller action.
 * Applies AuthMiddleware for routes marked with _auth = true.
 */
class Router
{
    private RouteCollection $routes;

    public function __construct(RouteCollection $routes)
    {
        $this->routes = $routes;
    }

    /**
     * Matches the request URL, applies auth middleware if required,
     * and invokes the resolved controller action.
     */
    public function dispatch(Request $request): SymfonyResponse
    {
        $context = new RequestContext();
        $context->fromRequest($request);

        $matcher = new UrlMatcher($this->routes, $context);

        try {
            $attributes = $matcher->match($request->getPathInfo());
        } catch (ResourceNotFoundException) {
            return Response::error('Route not found.', 404);
        } catch (MethodNotAllowedException) {
            return Response::error('Method not allowed.', 405);
        }

        // Extract route metadata.
        $controllerDef = $attributes['_controller'];
        $requiresAuth  = $attributes['_auth'] ?? false;

        // Path parameters: everything that is not a special Symfony attribute.
        $params = array_filter(
            $attributes,
            static fn(string $key) => !str_starts_with($key, '_'),
            ARRAY_FILTER_USE_KEY
        );

        // Authenticate if the route requires it.
        $authUser = [];
        if ($requiresAuth) {
            $result = AuthMiddleware::authenticate($request);

            if ($result instanceof SymfonyResponse) {
                // Authentication failed; return the error response directly.
                return $result;
            }

            $authUser = $result;
        }

        // Resolve and call the controller action.
        [$controllerClass, $method] = $controllerDef;

        if (!class_exists($controllerClass)) {
            return Response::error('Controller not found.', 500);
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            return Response::error('Controller action not found.', 500);
        }

        // Build the argument list: $request, $authUser, then path params in order.
        $args = [$request, $authUser];

        // Append numeric path parameters cast to int (e.g. {id}).
        foreach ($params as $key => $value) {
            $args[] = (int) $value;
        }

        return $controller->$method(...$args);
    }
}
