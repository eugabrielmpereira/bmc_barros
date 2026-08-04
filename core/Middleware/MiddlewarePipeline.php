<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Executa middlewares em cadeia; o destino final (controller) é o último elo.
 */
final class MiddlewarePipeline
{
    /** @var MiddlewareInterface[] */
    private array $middlewares;

    /**
     * @param MiddlewareInterface[] $middlewares
     */
    public function __construct(array $middlewares = [])
    {
        $this->middlewares = $middlewares;
    }

    /**
     * @param callable(Request): Response $destination Controller ou handler final
     */
    public function handle(Request $request, callable $destination): Response
    {
        $next = $destination;

        foreach (array_reverse($this->middlewares) as $middleware) {
            $next = function (Request $request) use ($middleware, $next): Response {
                return $middleware->handle($request, $next);
            };
        }

        return $next($request);
    }
}
