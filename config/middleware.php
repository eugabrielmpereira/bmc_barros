<?php

declare(strict_types=1);

/**
 * Grupos de middleware por tipo de rota.
 * Classes listadas são executadas na ordem definida (antes do controller).
 *
 * Exemplo (futuro):
 *   \Core\Middleware\AuthMiddleware::class,
 */
return [
    'web' => [
        // Rotas públicas (home, catálogo)
    ],

    'admin' => [
        \Core\Middleware\AuthMiddleware::class,
        \Core\Middleware\AclMiddleware::class,
    ],

    'customer' => [
        \Core\Middleware\CustomerAuthMiddleware::class,
    ],
];
