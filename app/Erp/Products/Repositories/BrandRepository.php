<?php

declare(strict_types=1);

namespace Erp\Products\Repositories;

use Core\Database\Repository;
use PDO;

/**
 * Marcas do catálogo para filtros e fichas de produto.
 */
final class BrandRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('brands', $pdo);
    }

    /**
     * Marcas com produtos ativos (para filtro lateral da vitrine).
     *
     * @return list<array<string, mixed>>
     */
    public function listForCatalogFilter(): array
    {
        if (!$this->tableExists()) {
            return [];
        }

        $sql = "SELECT b.id, b.name, b.slug, COUNT(p.id) AS product_count
                FROM {$this->table} b
                INNER JOIN produtos p ON p.brand_id = b.id AND p.status = 'ativo'
                WHERE b.status = 'active'
                GROUP BY b.id, b.name, b.slug
                HAVING product_count > 0
                ORDER BY b.sort_order, b.name";
        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findActiveBySlug(string $slug): ?array
    {
        if (!$this->tableExists()) {
            return null;
        }

        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE slug = ? AND status = 'active' LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    private function tableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='brands'");
            $exists = $stmt !== false && $stmt->fetchColumn() !== false;

            return $exists;
        }

        $stmt = $this->db->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute(['brands']);
        $exists = $stmt->fetchColumn() !== false;

        return $exists;
    }
}
