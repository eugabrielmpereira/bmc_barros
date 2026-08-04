<?php

declare(strict_types=1);

/**
 * PHPUnit — define constantes mínimas antes do autoload carregar functions.php.
 */
$root = dirname(__DIR__);

if (!defined('APP_ENV')) {
    define('APP_ENV', 'testing');
}
if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $root);
}
if (!defined('APP_PATH')) {
    define('APP_PATH', BASE_PATH . '/app');
}
if (!defined('ERP_PATH')) {
    define('ERP_PATH', APP_PATH . '/Erp');
}
if (!defined('CLIENTS_PATH')) {
    define('CLIENTS_PATH', APP_PATH . '/Clients');
}
if (!defined('SHARED_PATH')) {
    define('SHARED_PATH', APP_PATH . '/Shared');
}
if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', BASE_PATH . '/storage');
}
if (!defined('PUBLIC_PATH')) {
    define('PUBLIC_PATH', BASE_PATH . '/public');
}
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/bmc_barros/public');
}
if (!defined('ACTIVE_CLIENT')) {
    define('ACTIVE_CLIENT', 'bmc');
}
if (!defined('CSRF_TOKEN_LENGTH')) {
    define('CSRF_TOKEN_LENGTH', 16);
}
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800);
}

require $root . '/vendor/autoload.php';

// Testes unitários usam PDO injetado; evita MULTITENANT_DB=true vazando do shell/.env.
$_ENV['MULTITENANT_DB'] = 'false';
$_SERVER['MULTITENANT_DB'] = 'false';
\Core\Database::resetForTesting();
