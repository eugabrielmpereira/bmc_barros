<?php

declare(strict_types=1);

namespace Core;

/**
 * Wrapper mínimo da requisição HTTP atual (superglobals).
 */
class Request
{
    public function __construct(
        private string $method,
        private string $uri,
        private array $query = [],
        private array $post = [],
        private array $server = []
    ) {
        $this->method = strtoupper($method);
    }

    public static function capture(): self
    {
        $rawUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        // Não usar parse_url no path: "//produtos" vira host "produtos" e quebra o roteador.
        $uri = strstr($rawUri, '?', true) ?: $rawUri;
        $uri = preg_replace('#/+#', '/', (string) $uri) ?: '/';

        $basePath = (string) (parse_url(BASE_URL, PHP_URL_PATH) ?: '');
        $basePath = rtrim($basePath, '/');
        if ($basePath !== '' && str_starts_with($uri, $basePath)) {
            $uri = substr($uri, strlen($basePath));
        }

        $uri = '/' . trim($uri, '/');

        return new self(
            $_SERVER['REQUEST_METHOD'] ?? 'GET',
            $uri,
            $_GET,
            $_POST,
            $_SERVER
        );
    }

    public function method(): string
    {
        return $this->method;
    }

    public function uri(): string
    {
        return $this->uri;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->post[$key] ?? $this->query[$key] ?? $default;
    }

    public function allPost(): array
    {
        return $this->post;
    }

    public function server(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }
}
