<?php

declare(strict_types=1);

namespace Core;

/**
 * Resposta HTTP enviada ao cliente após o pipeline de middleware.
 */
class Response
{
    private int $statusCode = 200;

    /** @var array<string, string> */
    private array $headers = [];

    private string $body = '';

    public static function empty(): self
    {
        return new self();
    }

    public static function html(string $body, int $statusCode = 200): self
    {
        $response = new self();
        $response->statusCode = $statusCode;
        $response->body = $body;
        $response->headers['Content-Type'] = 'text/html; charset=utf-8';

        return $response;
    }

    public static function redirect(string $url, int $statusCode = 302): self
    {
        $response = new self();
        $response->statusCode = $statusCode;
        $response->headers['Location'] = $url;

        return $response;
    }

    public function status(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function body(string $content): self
    {
        $this->body = $content;

        return $this;
    }

    public function send(): void
    {
        if (headers_sent()) {
            if ($this->body !== '') {
                echo $this->body;
            }

            return;
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("{$name}: {$value}");
        }

        if ($this->body !== '') {
            echo $this->body;
        }
    }
}
