<?php

declare(strict_types=1);

namespace Erp\Products\Repositories;

use PDO;

/**
 * Facetas de atributos para filtros do catálogo público.
 */
final class AttributeFacetRepository
{
    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo ??= \Core\Database::getInstance();
    }

    /**
     * Atributos filtráveis com opções que possuem produtos ativos.
     *
     * @return list<array{
     *     id: int,
     *     code: string,
     *     name: string,
     *     options: list<array{id: int, value: string, slug: string, product_count: int}>
     * }>
     */
    public function listForCatalog(): array
    {
        if (!$this->tablesExist()) {
            return [];
        }

        $sql = <<<'SQL'
            SELECT ad.id, ad.code, ad.name, ad.sort_order
            FROM attribute_definitions ad
            WHERE ad.is_filterable = 1
            ORDER BY ad.sort_order, ad.name
        SQL;
        $attributes = $this->pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $result = [];

        $optionStmt = $this->pdo->prepare(
            <<<'SQL'
            SELECT ao.id, ao.value, ao.slug, COUNT(DISTINCT p.id) AS product_count
            FROM attribute_options ao
            INNER JOIN product_attribute_values pav ON pav.attribute_option_id = ao.id
            INNER JOIN produtos p ON p.id = pav.product_id AND p.status = 'ativo'
            WHERE ao.attribute_id = ?
            GROUP BY ao.id, ao.value, ao.slug
            HAVING product_count > 0
            ORDER BY ao.sort_order, ao.value
            SQL
        );

        foreach ($attributes as $attribute) {
            $optionStmt->execute([(int) $attribute['id']]);
            $options = $optionStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if ($options === []) {
                continue;
            }

            $result[] = [
                'id' => (int) $attribute['id'],
                'code' => (string) $attribute['code'],
                'name' => (string) $attribute['name'],
                'options' => array_map(static fn (array $row): array => [
                    'id' => (int) $row['id'],
                    'value' => (string) $row['value'],
                    'slug' => (string) $row['slug'],
                    'product_count' => (int) $row['product_count'],
                ], $options),
            ];
        }

        return $result;
    }

    /**
     * @param list<string> $slugs
     * @return list<int>
     */
    public function resolveOptionIdsBySlugs(array $slugs): array
    {
        if (!$this->tablesExist() || $slugs === []) {
            return [];
        }

        $slugs = array_values(array_unique(array_filter(array_map('trim', $slugs))));
        if ($slugs === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($slugs), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT id FROM attribute_options WHERE slug IN ({$placeholders})"
        );
        $stmt->execute($slugs);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    }

    /**
     * @param list<int> $optionIds
     * @return list<string>
     */
    public function slugsByOptionIds(array $optionIds): array
    {
        if (!$this->tablesExist() || $optionIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($optionIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT slug FROM attribute_options WHERE id IN ({$placeholders}) ORDER BY slug"
        );
        $stmt->execute($optionIds);

        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    private function tablesExist(): bool
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'sqlite') {
            $stmt = $this->pdo->query(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = 'attribute_definitions' LIMIT 1"
            );

            return $stmt !== false && $stmt->fetchColumn() !== false;
        }

        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute(['attribute_definitions']);

        return $stmt->fetchColumn() !== false;
    }
}
