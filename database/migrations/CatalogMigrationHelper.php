<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

/**
 * Utilitários compartilhados entre migrations do catálogo profissional.
 */
final class CatalogMigrationHelper
{
    public static function slugify(string $value): string
    {
        $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
        $value = preg_replace('/[^A-Za-z0-9]+/', '-', (string) $value);
        $value = trim((string) $value, '-');

        return strtolower($value);
    }

    /**
     * @param array<string, mixed> $scopeColumns
     */
    public static function uniqueSlug(
        PDO $pdo,
        string $table,
        string $baseSlug,
        int $excludeId = 0,
        array $scopeColumns = []
    ): string {
        $slug = $baseSlug !== '' ? $baseSlug : 'item';
        $candidate = $slug;
        $suffix = 2;

        while (self::slugExists($pdo, $table, $candidate, $excludeId, $scopeColumns)) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    /**
     * @param array<string, mixed> $scopeColumns
     */
    private static function slugExists(
        PDO $pdo,
        string $table,
        string $slug,
        int $excludeId,
        array $scopeColumns
    ): bool {
        $where = ['slug = ?'];
        $params = [$slug];

        foreach ($scopeColumns as $column => $value) {
            if ($value === null) {
                $where[] = "`{$column}` IS NULL";
                continue;
            }
            $where[] = "`{$column}` = ?";
            $params[] = $value;
        }

        if ($excludeId > 0) {
            $where[] = 'id <> ?';
            $params[] = $excludeId;
        }

        $sql = "SELECT 1 FROM `{$table}` WHERE " . implode(' AND ', $where) . ' LIMIT 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public static function backfillCategorySlugs(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, nome FROM categorias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE categorias SET slug = ? WHERE id = ?');

        foreach ($rows ?: [] as $row) {
            $base = self::slugify((string) $row['nome']);
            $slug = self::uniqueSlug($pdo, 'categorias', $base, (int) $row['id']);
            $update->execute([$slug, (int) $row['id']]);
        }
    }

    public static function backfillProductSlugs(PDO $pdo): void
    {
        $rows = $pdo->query('SELECT id, nome FROM produtos ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $update = $pdo->prepare('UPDATE produtos SET slug = ? WHERE id = ?');

        foreach ($rows ?: [] as $row) {
            $id = (int) $row['id'];
            $base = $id . '-' . self::slugify((string) $row['nome']);
            $slug = self::uniqueSlug($pdo, 'produtos', $base, $id);
            $update->execute([$slug, $id]);
        }
    }

    public static function copyDescriptionToLong(PDO $pdo): void
    {
        $pdo->exec(
            'UPDATE produtos SET long_description = descricao
             WHERE (long_description IS NULL OR long_description = "")
               AND descricao IS NOT NULL AND descricao <> ""'
        );
    }

    public static function syncProductPrimaryCategories(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'product_categories')) {
            return;
        }

        $rows = $pdo->query('SELECT id, categoria_id FROM produtos')->fetchAll(PDO::FETCH_ASSOC);
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO product_categories (product_id, category_id, is_primary)
             VALUES (?, ?, 1)'
        );

        foreach ($rows ?: [] as $row) {
            $insert->execute([(int) $row['id'], (int) $row['categoria_id']]);
        }
    }

    public static function migratePrimaryImagesToGallery(PDO $pdo): void
    {
        if (!self::tableExists($pdo, 'product_images')) {
            return;
        }

        $rows = $pdo->query(
            "SELECT id, imagem FROM produtos WHERE imagem IS NOT NULL AND imagem <> ''"
        )->fetchAll(PDO::FETCH_ASSOC);
        $insert = $pdo->prepare(
            'INSERT IGNORE INTO product_images (product_id, path, sort_order, is_primary)
             VALUES (?, ?, 0, 1)'
        );

        foreach ($rows ?: [] as $row) {
            $insert->execute([(int) $row['id'], (string) $row['imagem']]);
        }
    }

    public static function tableExists(PDO $pdo, string $table): bool
    {
        $stmt = $pdo->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute([$table]);

        return $stmt->fetchColumn() !== false;
    }
}
