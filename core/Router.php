<?php

declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    /**
     * @param callable|array{class-string, string} $handler
     */
    public function add(
        string $method,
        string $path,
        callable|array $handler,
        string $middleware = 'web'
    ): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * @return array{handler: callable|array, params: array<string, string>, middleware: string}|null
     */
    public function resolve(string $method, string $uri): ?array
    {
        $method = strtoupper($method);

        foreach ($this->routes as $route) {
            $params = [];
            if ($route['method'] === $method && $this->matchPath($route['path'], $uri, $params)) {
                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                    'middleware' => $route['middleware'],
                ];
            }
        }

        return null;
    }

    private function matchPath(string $routePath, string $uri, &$params = []): bool
    {
        $routeParts = explode('/', trim($routePath, '/'));
        $uriParts = explode('/', trim($uri, '/'));

        if (count($routeParts) !== count($uriParts)) {
            return false;
        }

        $params = [];
        foreach ($routeParts as $index => $part) {
            if (str_starts_with($part, ':')) {
                $paramName = substr($part, 1);
                $params[$paramName] = $uriParts[$index];
            } elseif ($part !== $uriParts[$index]) {
                return false;
            }
        }

        return true;
    }
}
