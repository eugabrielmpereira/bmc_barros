<?php

declare(strict_types=1);

/**
 * Registro de clientes (branding, rotas, ClientConfig).
 * Novos tenants adicionados via `php tenant.php tenant:create` (scaffold).
 */
return [
    'acme2' => [
        'config' => Clients\Acme2\Config\ClientConfig::class,
        'folder' => 'Acme2',
        'routes' => '/Clients/Acme2/routes.php',
    ],
    'bmc' => [
        'config' => Clients\Bmc\Config\ClientConfig::class,
        'folder' => 'Bmc',
        'routes' => '/Clients/Bmc/routes.php',
    ],
    'demo' => [
        'config' => Clients\Demo\Config\ClientConfig::class,
        'folder' => 'Demo',
        'routes' => '/Clients/Demo/routes.php',
    ],
];
