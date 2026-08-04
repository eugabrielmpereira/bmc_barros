<?php

declare(strict_types=1);

namespace Core;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = null): void
    {
        renderView($view, $data, $layout);
    }

    protected function redirect(string $url): void
    {
        header("Location: " . BASE_URL . $url);
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}