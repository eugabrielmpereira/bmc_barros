<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Exemplo de middleware (não registrado por padrão).
 * Para testar: adicione ExampleMiddleware::class em config/middleware.php no grupo desejado.
 */
final class ExampleMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $response = $next($request);

        return $response->header('X-ERP-Middleware', 'ExampleMiddleware');
    }
}
