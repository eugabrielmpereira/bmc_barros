<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

define('APP_ENV', $_ENV['APP_ENV'] ?? 'local');
define('APP_DEBUG', $_ENV['APP_DEBUG'] ?? false);
define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('ERP_PATH', APP_PATH . '/Erp');
define('CLIENTS_PATH', APP_PATH . '/Clients');
define('SHARED_PATH', APP_PATH . '/Shared');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

$tenantConfig = require __DIR__ . '/tenants.php';
$tenantResolution = (string) ($tenantConfig['resolution'] ?? 'env');
$tenantKnownClients = (array) ($tenantConfig['known_clients'] ?? ['bmc', 'demo']);
$tenantHostMap = (array) ($tenantConfig['hosts'] ?? []);
$tenantDefaultClient = (string) ($tenantConfig['default_client'] ?? 'bmc');
$tenantBaseUrls = (array) ($tenantConfig['base_urls'] ?? []);
$envBaseUrl = (string) ($_ENV['BASE_URL'] ?? 'http://localhost/bmc_barros/public');
$envActiveClient = (string) ($_ENV['ACTIVE_CLIENT'] ?? $tenantDefaultClient);

$httpHost = isset($_SERVER['HTTP_HOST']) ? (string) $_SERVER['HTTP_HOST'] : null;

try {
    $resolvedClient = \Shared\Support\MultitenantBootstrap::resolveClientConfigKey(
        $tenantResolution,
        $httpHost,
        $envActiveClient,
        $tenantHostMap,
        $tenantDefaultClient,
        $tenantKnownClients
    );
} catch (\Core\Exceptions\TenantNotFoundException) {
    http_response_code(503);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Serviço temporariamente indisponível.';
    exit;
}

define('TENANT_RESOLUTION', $tenantResolution);
define('ACTIVE_CLIENT', $resolvedClient);
define(
    'BASE_URL',
    rtrim(
        \Shared\Support\TenantResolver::resolveBaseUrl(
            $tenantResolution,
            $resolvedClient,
            $_SERVER ?? null,
            $envBaseUrl,
            $tenantBaseUrls
        ),
        '/'
    )
);

define('CSRF_TOKEN_LENGTH', (int)($_ENV['CSRF_TOKEN_LENGTH'] ?? 32));
define('SESSION_TIMEOUT', (int)($_ENV['SESSION_TIMEOUT'] ?? 1800));

if (\Core\Database::isMultitenantEnabled()) {
    try {
        \Core\Database::initializeForRequest(ACTIVE_CLIENT);
    } catch (\Core\Exceptions\TenantSuspendedException) {
        http_response_code(503);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Serviço temporariamente indisponível.';
        exit;
    }
}

session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'samesite' => 'Strict'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

\Core\Security\SessionGuard::enforce();

ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php_error.log');

if (is_production()) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
}

error_reporting(E_ALL);