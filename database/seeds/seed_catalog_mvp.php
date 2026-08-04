#!/usr/bin/env php
<?php

/**
 * Seed MVP do catálogo profissional (categorias hierárquicas, marcas e produtos).
 *
 * Uso:
 *   php migrate.php run
 *   php database/seeds/seed_catalog_mvp.php
 *   php database/seeds/seed_catalog_mvp.php --force
 */

declare(strict_types=1);

use Core\Database;
use Erp\Products\Support\CatalogSlug;

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Execute apenas via linha de comando.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

$force = in_array('--force', $argv, true);
$pdo = Database::getInstance();

if (!tableExists($pdo, 'categorias') || !columnExists($pdo, 'categorias', 'parent_id')) {
    fwrite(STDERR, "Schema de catálogo incompleto. Execute: php migrate.php run\n");
    exit(1);
}

$productCount = (int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn();
if ($productCount > 0 && !$force) {
    fwrite(STDERR, "Catálogo já possui {$productCount} produto(s). Use --force para recriar o seed MVP.\n");
    exit(1);
}

/** @var array<string, mixed> $catalog */
$catalog = require __DIR__ . '/data/catalog_mvp_data.php';

$pdo->beginTransaction();

try {
    if ($force) {
        clearCatalogData($pdo);
    }

    $brandMap = seedBrands($pdo, $catalog['brands']);
    $unitMap = loadUnitMap($pdo);

    $stats = ['categories' => 0, 'products' => 0];

    foreach ($catalog['departments'] as $department) {
        $rootId = insertCategory($pdo, $department, null, 1);
        $stats['categories']++;

        foreach ($department['children'] as $child) {
            $childId = insertCategory($pdo, $child, $rootId, 2);
            $stats['categories']++;

            foreach ($child['products'] as $product) {
                insertProduct($pdo, $product, $childId, $brandMap, $unitMap);
                $stats['products']++;
            }
        }
    }

    $pdo->commit();

    fwrite(STDOUT, "Seed MVP concluído com sucesso.\n");
    fwrite(STDOUT, "  Categorias: {$stats['categories']}\n");
    fwrite(STDOUT, "  Produtos:   {$stats['products']}\n");
    fwrite(STDOUT, "  Marcas:     " . count($brandMap) . "\n");
    exit(0);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    fwrite(STDERR, 'Erro no seed: ' . $e->getMessage() . "\n");
    exit(1);
}

/**
 * @param array<string, mixed> $data
 */
function insertCategory(PDO $pdo, array $data, ?int $parentId, int $level): int
{
    $stmt = $pdo->prepare(
        'INSERT INTO categorias (
            parent_id, nome, slug, description, sort_order, level, status,
            meta_title, meta_description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $parentId,
        $data['nome'],
        $data['slug'],
        $data['meta_description'] ?? null,
        (int) ($data['sort_order'] ?? 0),
        $level,
        'active',
        $data['nome'],
        $data['meta_description'] ?? null,
    ]);

    return (int) $pdo->lastInsertId();
}

/**
 * @param array<string, mixed> $product
 * @param array<string, int> $brandMap
 * @param array<string, int> $unitMap
 */
function insertProduct(PDO $pdo, array $product, int $categoryId, array $brandMap, array $unitMap): void
{
    $brandId = $brandMap[$product['brand_slug']] ?? null;
    $unitId = $unitMap[$product['unit_code']] ?? null;
    $descricao = (string) $product['long_description'];

    $stmt = $pdo->prepare(
        'INSERT INTO produtos (
            categoria_id, nome, slug, sku, short_description, long_description, descricao,
            brand_id, unit_id, preco, compare_at_price, estoque_atual, status,
            meta_title, meta_description
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $categoryId,
        $product['nome'],
        'seed-' . strtolower((string) $product['sku']),
        $product['sku'],
        $product['short_description'],
        $product['long_description'],
        $descricao,
        $brandId,
        $unitId,
        $product['preco'],
        $product['compare_at_price'] ?? null,
        (int) $product['estoque'],
        'ativo',
        $product['nome'],
        $product['short_description'],
    ]);

    $productId = (int) $pdo->lastInsertId();
    $slug = CatalogSlug::forProduct($productId, (string) $product['nome']);

    $pdo->prepare('UPDATE produtos SET slug = ? WHERE id = ?')->execute([$slug, $productId]);

    if (tableExists($pdo, 'product_categories')) {
        $pdo->prepare(
            'INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (?, ?, 1)'
        )->execute([$productId, $categoryId]);
    }
}

/**
 * @param list<array{name: string, slug: string}> $brands
 * @return array<string, int>
 */
function seedBrands(PDO $pdo, array $brands): array
{
    $map = [];
    $stmt = $pdo->prepare(
        'INSERT INTO brands (name, slug, status, sort_order) VALUES (?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE name = VALUES(name)'
    );

    $sort = 1;
    foreach ($brands as $brand) {
        $stmt->execute([$brand['name'], $brand['slug'], 'active', $sort]);
        $id = (int) $pdo->lastInsertId();
        if ($id === 0) {
            $lookup = $pdo->prepare('SELECT id FROM brands WHERE slug = ? LIMIT 1');
            $lookup->execute([$brand['slug']]);
            $id = (int) $lookup->fetchColumn();
        }
        $map[$brand['slug']] = $id;
        $sort++;
    }

    return $map;
}

/** @return array<string, int> */
function loadUnitMap(PDO $pdo): array
{
    $map = [];
    $rows = $pdo->query('SELECT id, code FROM measurement_units')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows ?: [] as $row) {
        $map[(string) $row['code']] = (int) $row['id'];
    }

    return $map;
}

function clearCatalogData(PDO $pdo): void
{
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    foreach ([
        'cart_items',
        'product_variants',
        'product_attribute_values',
        'product_images',
        'product_categories',
        'stock_movements',
        'order_items',
        'produtos',
        'categorias',
        'brands',
    ] as $table) {
        if (tableExists($pdo, $table)) {
            $pdo->exec("DELETE FROM `{$table}`");
        }
    }
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    fwrite(STDOUT, "Catálogo anterior removido (--force).\n");
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT 1 FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
    );
    $stmt->execute([$table]);

    return $stmt->fetchColumn() !== false;
}

function columnExists(PDO $pdo, string $table, string $column): bool
{
    $column = str_replace('`', '', $column);
    $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

    return $stmt !== false && $stmt->fetch() !== false;
}
