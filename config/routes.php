<?php

declare(strict_types=1);

/**
 * Cada rota pode definir 'middleware' => 'web' | 'admin' (ver config/middleware.php).
 */
$routeFiles = [
    __DIR__ . '/routes/clients.php',
    __DIR__ . '/routes/erp.php',
];

$routes = [];
foreach ($routeFiles as $file) {
    $routes = array_merge($routes, require $file);
}

return $routes;
