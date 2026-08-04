<?php

declare(strict_types=1);

namespace Erp\Products\Repositories;

use Core\Database\Repository;
use Erp\Products\Support\CatalogSlug;
use PDO;

/**
 * Acesso a dados de categorias hierárquicas do catálogo.
 */
final class CategoryRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('categorias', $pdo);
    }

    public function findById(int $id): ?array
    {
        return $this->find($id);
    }

    public function findActiveBySlug(string $slug): ?array
    {
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

    /**
     * @return list<array<string, mixed>>
     */
    public function listAllOrdered(): array
    {
        $stmt = $this->db->query(
            "SELECT c.*, parent.nome AS parent_nome
             FROM {$this->table} c
             LEFT JOIN {$this->table} parent ON parent.id = c.parent_id
             ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.sort_order, c.nome"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Árvore para menu da vitrine (raízes + filhos ativos).
     *
     * @return list<array<string, mixed>>
     */
    public function getCatalogTree(): array
    {
        if (!$this->hasHierarchy()) {
            return array_map(fn (array $row): array => array_merge($row, ['children' => []]), $this->listRoots());
        }

        $roots = $this->listRoots();
        foreach ($roots as &$root) {
            $root['children'] = $this->listChildren((int) $root['id']);
        }
        unset($root);

        return $roots;
    }

    /**
     * Trilha de breadcrumbs da categoria até a raiz.
     *
     * @return list<array{id: int, nome: string, slug: string}>
     */
    public function getBreadcrumbs(int $categoryId): array
    {
        $trail = [];
        $currentId = $categoryId;
        $guard = 0;

        while ($currentId > 0 && $guard < 5) {
            $category = $this->findById($currentId);
            if ($category === null) {
                break;
            }
            array_unshift($trail, [
                'id' => (int) $category['id'],
                'nome' => (string) $category['nome'],
                'slug' => (string) ($category['slug'] ?? CatalogSlug::fromText((string) $category['nome'])),
            ]);
            $parentId = $category['parent_id'] ?? null;
            $currentId = $parentId !== null ? (int) $parentId : 0;
            $guard++;
        }

        return $trail;
    }

    /**
     * Subcategorias folha para select de produto (agrupadas por departamento no admin).
     *
     * @return list<array<string, mixed>>
     */
    public function listLeafCategoriesForProductForm(): array
    {
        if (!$this->hasHierarchy()) {
            $stmt = $this->db->query("SELECT id, nome, NULL AS parent_nome FROM {$this->table} ORDER BY nome");

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->db->query(
            "SELECT child.id, child.nome, parent.nome AS parent_nome
             FROM {$this->table} child
             LEFT JOIN {$this->table} parent ON parent.id = child.parent_id
             WHERE child.status = 'active'
               AND child.level = 2
             ORDER BY parent.sort_order, parent.nome, child.sort_order, child.nome"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listParentOptions(?int $excludeId = null): array
    {
        $sql = "SELECT id, nome, level, parent_id FROM {$this->table}
                WHERE status = 'active' AND level < 3";
        $params = [];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' ORDER BY level, sort_order, nome';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countChildren(int $categoryId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE parent_id = ?");
        $stmt->execute([$categoryId]);

        return (int) $stmt->fetchColumn();
    }

    public function countProducts(int $categoryId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM produtos WHERE categoria_id = ?');
        $stmt->execute([$categoryId]);

        return (int) $stmt->fetchColumn();
    }

    public function slugExists(string $slug, int $excludeId = 0): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE slug = ?";
        $params = [$slug];
        if ($excludeId > 0) {
            $sql .= ' AND id <> ?';
            $params[] = $excludeId;
        }
        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): int
    {
        $this->create($data);

        return (int) $this->db->lastInsertId();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listRoots(): array
    {
        if ($this->hasHierarchy()) {
            $stmt = $this->db->query(
                "SELECT * FROM {$this->table}
                 WHERE status = 'active' AND (parent_id IS NULL OR level = 1)
                 ORDER BY sort_order, nome"
            );

            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY nome");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function listChildren(int $parentId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE parent_id = ? AND status = 'active'
             ORDER BY sort_order, nome"
        );
        $stmt->execute([$parentId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasHierarchy(): bool
    {
        static $has = null;
        if ($has !== null) {
            return $has;
        }

        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->query('PRAGMA table_info(categorias)');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $info) {
                if (($info['name'] ?? '') === 'parent_id') {
                    $has = true;

                    return true;
                }
            }
            $has = false;

            return false;
        }

        $stmt = $this->db->query("SHOW COLUMNS FROM categorias LIKE 'parent_id'");
        $has = $stmt !== false && $stmt->fetch() !== false;

        return $has;
    }
}
