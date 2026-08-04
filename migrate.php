#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/app.php';

use Core\Database;
use Core\Migrator;

if (php_sapi_name() !== 'cli') {
    echo "❌ This script can only be run from the CLI\n";
    exit(1);
}

$command = $argv[1] ?? 'run';
$pdo = Database::getInstance();
$migrator = new Migrator($pdo, __DIR__ . '/database/migrations');

switch ($command) {
    case 'run':
    case 'migrate':
        $migrator->run();
        break;

    case 'rollback':
        $migrator->rollback();
        break;

    default:
        echo "Usage: php migrate.php [run|rollback]\n";
        exit(1);
}

exit(0);
