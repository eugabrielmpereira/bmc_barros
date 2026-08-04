#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Migrations do banco mestre (erp_master).
 * Não carrega config/app.php — evita bootstrap de sessão e resolução de tenant.
 *
 * Uso:
 *   php migrate_master.php run
 *   php migrate_master.php rollback
 *   php migrate_master.php seed
 */

require_once __DIR__ . '/vendor/autoload.php';

use Core\Database\MasterDatabase;
use Core\Migrator;

if (php_sapi_name() !== 'cli') {
    echo "❌ This script can only be run from the CLI\n";
    exit(1);
}

$dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

$command = $argv[1] ?? 'run';

if ($command === 'seed') {
    require __DIR__ . '/database/seeds/seed_master_tenants.php';
    exit(0);
}

try {
    MasterDatabase::ensureDatabaseExists();
    $pdo = MasterDatabase::getConnection();
} catch (Throwable $e) {
    echo '❌ Falha ao conectar ao banco mestre: ' . $e->getMessage() . "\n";
    exit(1);
}

$migrator = new Migrator($pdo, __DIR__ . '/database/migrations_master');

switch ($command) {
    case 'run':
    case 'migrate':
        $migrator->run();
        break;

    case 'rollback':
        $migrator->rollback();
        break;

    default:
        echo "Usage: php migrate_master.php [run|rollback|seed]\n";
        exit(1);
}

exit(0);
