#!/usr/bin/env php
<?php

/**
 * Zonas de entrega de EXEMPLO para começar.
 *
 * [REVISAR] Valores, bairros e prazos são ilustrativos — ajuste em /admin/frete antes do go-live.
 * Só insere se a tabela estiver vazia (não sobrescreve o que a loja já cadastrou).
 *
 * Uso: php database/seeds/seed_delivery_zones.php
 */

declare(strict_types=1);

use Core\Database;

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Execute apenas via linha de comando.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

$pdo = Database::getInstance();

$count = (int) $pdo->query('SELECT COUNT(*) FROM delivery_zones')->fetchColumn();
if ($count > 0) {
    echo "Já existem {$count} zona(s) cadastrada(s). Nada a fazer.\n";
    exit(0);
}

$zones = [
    [
        'name' => 'Rincão — cidade',
        'city' => 'Rincão',
        'state' => 'SP',
        'neighborhoods' => null,
        'fee' => 15.00,
        'free_shipping_min' => 500.00,
        'min_order_amount' => null,
        'lead_time' => 'Entrega em até 1 dia útil',
        'sort_order' => 10,
    ],
    [
        'name' => 'Taquaral',
        'city' => 'Taquaral',
        'state' => 'SP',
        'neighborhoods' => null,
        'fee' => 35.00,
        'free_shipping_min' => 1000.00,
        'min_order_amount' => 150.00,
        'lead_time' => 'Entrega em até 2 dias úteis',
        'sort_order' => 20,
    ],
];

$stmt = $pdo->prepare(
    'INSERT INTO delivery_zones (name, city, state, neighborhoods, fee, free_shipping_min, min_order_amount, lead_time, sort_order, status)
     VALUES (:name, :city, :state, :neighborhoods, :fee, :free_shipping_min, :min_order_amount, :lead_time, :sort_order, \'active\')'
);

foreach ($zones as $zone) {
    $stmt->execute($zone);
}

echo count($zones) . " zona(s) de exemplo criadas. Revise valores em /admin/frete.\n";
