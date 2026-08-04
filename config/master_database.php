<?php

declare(strict_types=1);

/**
 * Conexão ao banco mestre (registro de tenants).
 * Reutiliza DB_HOST, DB_USER e DB_PASS do operador.
 */
return [
    'host' => (string) env('DB_HOST', 'localhost'),
    'database' => (string) env('MASTER_DB_NAME', 'erp_master'),
    'username' => (string) env('DB_USER', 'root'),
    'password' => (string) env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
