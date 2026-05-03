<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles CORS headers and OPTIONS preflight requests.
 */
class CorsMiddleware
{
    private const ALLOW_ORIGIN  = '*';
    private const ALLOW_METHODS = 'GET, POST, PUT, DELETE, OPTIONS';
    private const ALLOW_HEADERS = 'Content-Type, Authorization';

    /**
     * Adds CORS headers to the response.
     * If the request method is OPTIONS, returns a 204 response immediately.
     *
     * @param Request  $request The incoming HTTP request.
     * @param callable $next    The next handler; receives $request and must return a Response.
     * @return Response
     */
    public static function handle(Request $request, callable $next): Response
    {
        // Handle preflight OPTIONS requests immediately.
        if ($request->getMethod() === 'OPTIONS') {
            $response = new Response('', 204);
            self::addHeaders($response);
            return $response;
        }

        /** @var Response $response */
        $response = $next($request);
        self::addHeaders($response);

        return $response;
    }

    /**
     * Attaches the CORS headers to a response object.
     */
    private static function addHeaders(Response $response): void
    {
        $response->headers->set('Access-Control-Allow-Origin',  self::ALLOW_ORIGIN);
        $response->headers->set('Access-Control-Allow-Methods', self::ALLOW_METHODS);
        $response->headers->set('Access-Control-Allow-Headers', self::ALLOW_HEADERS);
    }
}
