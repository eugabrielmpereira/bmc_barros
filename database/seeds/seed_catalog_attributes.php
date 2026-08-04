#!/usr/bin/env php
<?php

/**
 * Atributos filtráveis e vínculo com produtos do catálogo MVP.
 *
 * Uso: php database/seeds/seed_catalog_attributes.php
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

if (!tableExists($pdo, 'attribute_definitions')) {
    fwrite(STDERR, "Tabelas de atributos ausentes. Execute: php migrate.php run\n");
    exit(1);
}

$definitions = [
    ['code' => 'material', 'name' => 'Material', 'options' => [
        ['value' => 'Aço', 'slug' => 'aco'],
        ['value' => 'PVC', 'slug' => 'pvc'],
        ['value' => 'Cerâmica', 'slug' => 'ceramica'],
        ['value' => 'Cimento', 'slug' => 'cimento'],
    ]],
    ['code' => 'usage', 'name' => 'Uso', 'options' => [
        ['value' => 'Uso interno', 'slug' => 'uso-interno'],
        ['value' => 'Uso externo', 'slug' => 'uso-externo'],
        ['value' => 'Estrutural', 'slug' => 'estrutural'],
    ]],
];

$pdo->beginTransaction();
try {
    foreach ($definitions as $def) {
        $stmt = $pdo->prepare(
            'INSERT INTO attribute_definitions (code, name, type, is_filterable, sort_order)
             VALUES (?, ?, ?, 1, 0)
             ON DUPLICATE KEY UPDATE name = VALUES(name), is_filterable = 1'
        );
        $stmt->execute([$def['code'], $def['name'], 'select']);
        $attrId = (int) $pdo->lastInsertId();
        if ($attrId === 0) {
            $lookup = $pdo->prepare('SELECT id FROM attribute_definitions WHERE code = ? LIMIT 1');
            $lookup->execute([$def['code']]);
            $attrId = (int) $lookup->fetchColumn();
        }

        foreach ($def['options'] as $i => $opt) {
            $pdo->prepare(
                'INSERT INTO attribute_options (attribute_id, value, slug, sort_order)
                 VALUES (?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE value = VALUES(value)'
            )->execute([$attrId, $opt['value'], $opt['slug'], $i + 1]);
        }
    }

    linkProductsByKeywords($pdo);
    $pdo->commit();
    fwrite(STDOUT, "Atributos e facetas vinculados aos produtos.\n");
    exit(0);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Erro: ' . $e->getMessage() . "\n");
    exit(1);
}

function linkProductsByKeywords(PDO $pdo): void
{
    $rules = [
        'aco' => ['prego', 'vergalhao', 'tela soldada', 'arame', 'esmerilhadeira', 'disco'],
        'pvc' => ['pvc', 'tubo', 'torneira', 'registro', 'esgoto'],
        'ceramica' => ['porcelanato', 'ceramica', 'revestimento'],
        'cimento' => ['cimento', 'argamassa', 'chapisco'],
        'uso-interno' => ['interno', 'lavabo', 'cozinha', 'banheiro', 'tomada', 'interruptor', 'lampada'],
        'uso-externo' => ['externo', 'fachada', 'refletor', 'impermeabilizante', 'antiderrapante'],
        'estrutural' => ['cimento', 'vergalhao', 'tela', 'estrutural', 'cp ii', 'cp iii'],
    ];

    $products = $pdo->query("SELECT id, nome FROM produtos WHERE status = 'ativo'")->fetchAll(PDO::FETCH_ASSOC);
    $optionMap = [];
    $opts = $pdo->query(
        'SELECT ao.id AS option_id, ao.slug, ao.attribute_id
         FROM attribute_options ao'
    );
    foreach ($opts->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
        $optionMap[(string) $row['slug']] = [
            'option_id' => (int) $row['option_id'],
            'attribute_id' => (int) $row['attribute_id'],
        ];
    }

    $insert = $pdo->prepare(
        'INSERT IGNORE INTO product_attribute_values (product_id, attribute_id, attribute_option_id)
         VALUES (?, ?, ?)'
    );

    foreach ($products as $product) {
        $name = mb_strtolower((string) $product['nome']);
        $productId = (int) $product['id'];
        $linkedAttrs = [];

        foreach ($rules as $optionSlug => $keywords) {
            if (!isset($optionMap[$optionSlug])) {
                continue;
            }
            foreach ($keywords as $keyword) {
                if (str_contains($name, $keyword)) {
                    $defId = (int) $optionMap[$optionSlug]['attribute_id'];
                    $optionId = (int) $optionMap[$optionSlug]['option_id'];
                    if ($defId > 0 && !isset($linkedAttrs[$defId])) {
                        $insert->execute([$productId, $defId, $optionId]);
                        $linkedAttrs[$defId] = true;
                    }
                    break;
                }
            }
        }
    }
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $stmt->execute([$table]);

    return $stmt->fetchColumn() !== false;
}
