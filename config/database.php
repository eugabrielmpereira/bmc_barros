<?php

declare(strict_types=1);

return [
    'host' => (string) env('DB_HOST', 'localhost'),
    'database' => (string) env('DB_NAME', 'erp_product'),
    'username' => (string) env('DB_USER', 'root'),
    'password' => (string) env('DB_PASS', ''),
    'charset' => 'utf8mb4',
];
