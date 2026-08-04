<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

interface MiddlewareInterface
{
    /**
     * @param callable(Request): Response $next
     */
    public function handle(Request $request, callable $next): Response;
}
