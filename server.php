<?php

/**
 * Roteador do servidor embutido do PHP (desenvolvimento local, sem Apache/XAMPP).
 *
 * Uso: php -S localhost:8000 -t public server.php
 *
 * Arquivos que existem em public/ (CSS, JS, imagens) são servidos direto;
 * todo o resto vai para public/index.php, como faz o .htaccess no Apache.
 */

declare(strict_types=1);

$publicDir = __DIR__ . DIRECTORY_SEPARATOR . 'public';
$path = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
$file = realpath($publicDir . $path);

if ($path !== '/' && $file !== false && is_file($file) && str_starts_with($file, realpath($publicDir))) {
    return false;
}

chdir($publicDir);
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['SCRIPT_FILENAME'] = $publicDir . DIRECTORY_SEPARATOR . 'index.php';

require $publicDir . DIRECTORY_SEPARATOR . 'index.php';
