#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * CLI de onboarding e gestão de tenants (Fase 4).
 *
 * Uso: php tenant.php help
 */

require_once __DIR__ . '/vendor/autoload.php';

use Core\Cli\TenantCommandHandler;

if (php_sapi_name() !== 'cli') {
    echo "❌ Apenas CLI\n";
    exit(1);
}

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Constantes mínimas para Client::isKnown() sem bootstrap HTTP completo.
if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
if (!defined('APP_ENV')) {
    define('APP_ENV', (string) env('APP_ENV', 'local'));
}

$handler = new TenantCommandHandler();
exit($handler->run($argv));
