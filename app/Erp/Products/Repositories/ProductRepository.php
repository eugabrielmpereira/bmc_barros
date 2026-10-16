<?php

declare(strict_types=1);

namespace Erp\Products\Repositories;

use Core\Database\Repository;
use Erp\Products\Support\CatalogProductFilter;
use Erp\Products\Support\CatalogSlug;
use PDO;

class ProductRepository extends Repository
{
    private ?bool $hasSlugColumn = null;

    private ?bool $hasFullTextSearch = null;

    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('produtos', $pdo);
    }

    /**
     * Listagem admin com join em categorias e busca opcional por nome.
     *
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(?string $busca = null): array
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM {$this->table} p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE 1=1";
        $params = [];

        $busca = $busca !== null ? trim($busca) : '';
        if ($busca !== '') {
            if ($this->supportsFullTextSearch()) {
                $sql .= ' AND MATCH(p.nome, p.short_description, p.long_description, p.descricao, p.sku)
                          AGAINST (? IN BOOLEAN MODE)';
                $params[] = $this->toFullTextQuery($busca);
            } else {
                $sql .= ' AND (p.nome LIKE ? OR p.sku LIKE ? OR p.descricao LIKE ?)';
                $term = '%' . $busca . '%';
                $params[] = $term;
                $params[] = $term;
                $params[] = $term;
            }
        }

        $sql .= ' ORDER BY p.status DESC, p.nome ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function allActive(?int $categoriaId = null, string $searchQuery = '', ?int $brandId = null): array
    {
        // searchActiveCatalog limita a página a 48 itens; percorre todas as páginas.
        $filter = new CatalogProductFilter($categoriaId, $searchQuery, $brandId);
        $pageSize = 48;
        $items = [];
        for ($offset = 0; ; $offset += $pageSize) {
            $page = $this->searchActiveCatalog($filter, $pageSize, $offset);
            $items = array_merge($items, $page['items']);
            if (count($page['items']) < $pageSize || count($items) >= (int) $page['total']) {
                break;
            }
        }

        return $items;
    }

    public function countActiveCatalog(CatalogProductFilter $filter): int
    {
        [$sql, $params] = $this->buildActiveCatalogQuery($filter, true);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int}
     */
    public function searchActiveCatalog(CatalogProductFilter $filter, int $limit, int $offset): array
    {
        $limit = max(1, min(48, $limit));
        $offset = max(0, $offset);
        $total = $this->countActiveCatalog($filter);

        if ($total === 0) {
            return ['items' => [], 'total' => 0];
        }

        [$sql, $params] = $this->buildActiveCatalogQuery($filter, false);
        $sql .= ' ORDER BY p.nome ASC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [],
            'total' => $total,
        ];
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildActiveCatalogQuery(CatalogProductFilter $filter, bool $countOnly): array
    {
        $select = $countOnly
            ? 'SELECT COUNT(DISTINCT p.id)'
            : "SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug";

        if (!$countOnly && $this->columnExistsOnTable($this->table, 'brand_id')) {
            $select .= ', b.name AS brand_name, b.slug AS brand_slug';
        }

        $sql = "{$select} FROM {$this->table} p
                JOIN categorias c ON c.id = p.categoria_id";
        if (!$countOnly && $this->columnExistsOnTable($this->table, 'brand_id')) {
            $sql .= ' LEFT JOIN brands b ON b.id = p.brand_id';
        }
        $sql .= " WHERE p.status = 'ativo'";
        $params = [];

        if ($filter->brandId !== null && $filter->brandId > 0) {
            $sql .= ' AND p.brand_id = ?';
            $params[] = $filter->brandId;
        }

        if ($filter->categoryId !== null) {
            $categoryIds = $this->resolveCategoryFilterIds($filter->categoryId);
            if ($categoryIds === []) {
                $sql .= ' AND 1 = 0';
            } else {
                $placeholders = implode(', ', array_fill(0, count($categoryIds), '?'));
                $sql .= " AND p.categoria_id IN ({$placeholders})";
                foreach ($categoryIds as $id) {
                    $params[] = $id;
                }
            }
        }

        $searchQuery = trim($filter->searchQuery);
        if ($searchQuery !== '') {
            if ($this->supportsFullTextSearch()) {
                $sql .= ' AND MATCH(p.nome, p.short_description, p.long_description, p.descricao, p.sku)
                          AGAINST (? IN BOOLEAN MODE)';
                $params[] = $this->toFullTextQuery($searchQuery);
            } else {
                $sql .= ' AND (p.nome LIKE ? OR p.descricao LIKE ? OR p.short_description LIKE ?
                            OR p.long_description LIKE ? OR c.nome LIKE ? OR p.sku LIKE ?)';
                $searchTerm = '%' . $searchQuery . '%';
                array_push($params, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm);
            }
        }

        $optionIds = array_values(array_unique(array_filter($filter->attributeOptionIds)));
        if ($optionIds !== [] && $this->attributeValuesTableExists()) {
            $placeholders = implode(', ', array_fill(0, count($optionIds), '?'));
            $sql .= " AND p.id IN (
                SELECT product_id FROM product_attribute_values
                WHERE attribute_option_id IN ({$placeholders})
                GROUP BY product_id
                HAVING COUNT(DISTINCT attribute_option_id) = ?
            )";
            foreach ($optionIds as $id) {
                $params[] = $id;
            }
            $params[] = count($optionIds);
        }

        return [$sql, $params];
    }

    private function attributeValuesTableExists(): bool
    {
        static $exists = null;
        if ($exists !== null) {
            return $exists;
        }

        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='product_attribute_values'");
            $exists = $stmt !== false && $stmt->fetchColumn() !== false;

            return $exists;
        }

        $stmt = $this->db->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute(['product_attribute_values']);
        $exists = $stmt->fetchColumn() !== false;

        return $exists;
    }

    public function findActiveByPublicSlug(string $slug): ?array
    {
        $slug = trim($slug);
        if ($slug === '') {
            return null;
        }

        if ($this->hasSlugColumn()) {
            $product = $this->findActiveWithCategoryBySlug($slug);
            if ($product !== null) {
                return $product;
            }
        }

        if (preg_match('/^(\d+)-/', $slug, $matches) === 1) {
            $product = $this->findActiveWithCategory((int) $matches[1]);
            if ($product !== null && $this->publicSlug($product) === $slug) {
                return $product;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $product
     */
    public function publicSlug(array $product): string
    {
        $stored = trim((string) ($product['slug'] ?? ''));
        if ($stored !== '') {
            return $stored;
        }

        return CatalogSlug::forProduct((int) ($product['id'] ?? 0), (string) ($product['nome'] ?? 'produto'));
    }

    /**
     * Categorias raiz ativas para menu do catálogo.
     *
     * @return list<array<string, mixed>>
     */
    /**
     * @deprecated Use CategoryRepository::getCatalogTree() na vitrine.
     * @return list<array<string, mixed>>
     */
    public function getCategorias(): array
    {
        return (new CategoryRepository($this->db))->getCatalogTree();
    }

    public function categoriaExists(int $categoriaId): bool
    {
        if ($categoriaId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare('SELECT 1 FROM categorias WHERE id = ? LIMIT 1');
        $stmt->execute([$categoriaId]);

        return $stmt->fetchColumn() !== false;
    }

    public function findCategoriaBySlug(string $slug): ?array
    {
        return (new CategoryRepository($this->db))->findActiveBySlug($slug);
    }

    public function syncPrimaryCategory(int $productId, int $categoryId): void
    {
        if ($productId <= 0 || $categoryId <= 0 || !$this->tableExists('product_categories')) {
            return;
        }

        $this->db->prepare('DELETE FROM product_categories WHERE product_id = ?')->execute([$productId]);
        $stmt = $this->db->prepare(
            'INSERT INTO product_categories (product_id, category_id, is_primary) VALUES (?, ?, 1)'
        );
        $stmt->execute([$productId, $categoryId]);
    }

    public function assignSlugAfterCreate(int $productId, string $name): string
    {
        $slug = CatalogSlug::forProduct($productId, $name);
        if ($this->hasSlugColumn()) {
            $slug = $this->ensureUniqueProductSlug($slug, $productId);
            $this->update($productId, ['slug' => $slug]);
        }

        return $slug;
    }

    public function countAllProducts(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");

        return (int) $stmt->fetchColumn();
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }

    public function countZeroStock(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE estoque_atual = 0");

        return (int) $stmt->fetchColumn();
    }

    public function countLowStockNonZero(): int
    {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE estoque_atual > 0 AND estoque_atual <= 3";
        $stmt = $this->db->query($sql);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return list<array{nome: string, estoque_atual: int|string, categoria: string}>
     */
    public function listActiveProductsLowStock(int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));
        $sql = "SELECT p.nome, p.estoque_atual, c.nome AS categoria
                FROM {$this->table} p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.estoque_atual <= 3 AND p.status = 'ativo'
                ORDER BY p.estoque_atual ASC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * IDs da categoria e descendentes (até 3 níveis) para filtro de vitrine.
     *
     * @return list<int>
     */
    private function resolveCategoryFilterIds(int $categoryId): array
    {
        if (!$this->hasCategoryHierarchy()) {
            return [$categoryId];
        }

        $ids = [];
        $queue = [$categoryId];
        $depth = 0;

        while ($queue !== [] && $depth < 3) {
            $next = [];
            foreach ($queue as $id) {
                $ids[] = $id;
                $stmt = $this->db->prepare(
                    'SELECT id FROM categorias WHERE parent_id = ? AND status = ?'
                );
                $stmt->execute([$id, 'active']);
                $children = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
                foreach ($children as $childId) {
                    $next[] = (int) $childId;
                }
            }
            $queue = $next;
            $depth++;
        }

        return array_values(array_unique($ids));
    }

    private function findActiveWithCategory(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        [$brandSelect, $brandJoin] = $this->brandSelectAndJoin();
        $sql = "SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug{$brandSelect}
                FROM {$this->table} p
                JOIN categorias c ON c.id = p.categoria_id{$brandJoin}
                WHERE p.id = ? AND p.status = 'ativo'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    private function findActiveWithCategoryBySlug(string $slug): ?array
    {
        [$brandSelect, $brandJoin] = $this->brandSelectAndJoin();
        $sql = "SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug{$brandSelect}
                FROM {$this->table} p
                JOIN categorias c ON c.id = p.categoria_id{$brandJoin}
                WHERE p.slug = ? AND p.status = 'ativo'
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function brandSelectAndJoin(): array
    {
        if (!$this->columnExistsOnTable($this->table, 'brand_id')) {
            return ['', ''];
        }

        return [', b.name AS brand_name, b.slug AS brand_slug', ' LEFT JOIN brands b ON b.id = p.brand_id'];
    }

    private function ensureUniqueProductSlug(string $slug, int $excludeId): string
    {
        $candidate = $slug;
        $suffix = 2;

        while ($this->slugExistsForProduct($candidate, $excludeId)) {
            $candidate = $slug . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function slugExistsForProduct(string $slug, int $excludeId): bool
    {
        $sql = 'SELECT 1 FROM produtos WHERE slug = ?';
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

    private function toFullTextQuery(string $query): string
    {
        $parts = preg_split('/\s+/', $query) ?: [];
        $terms = [];
        foreach ($parts as $part) {
            $part = preg_replace('/[^a-zA-Z0-9À-ÿ]/u', '', $part) ?? '';
            if ($part !== '') {
                $terms[] = '+' . $part . '*';
            }
        }

        return $terms !== [] ? implode(' ', $terms) : $query;
    }

    private function hasSlugColumn(): bool
    {
        if ($this->hasSlugColumn !== null) {
            return $this->hasSlugColumn;
        }

        $this->hasSlugColumn = $this->columnExistsOnTable($this->table, 'slug');

        return $this->hasSlugColumn;
    }

    private function hasCategoryHierarchy(): bool
    {
        return $this->columnExistsOnTable('categorias', 'parent_id');
    }

    private function columnExistsOnTable(string $table, string $column): bool
    {
        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->query("PRAGMA table_info({$table})");
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $info) {
                if (($info['name'] ?? '') === $column) {
                    return true;
                }
            }

            return false;
        }

        $column = str_replace("'", '', $column);
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

        return $stmt !== false && $stmt->fetch() !== false;
    }

    private function supportsFullTextSearch(): bool
    {
        if ($this->hasFullTextSearch !== null) {
            return $this->hasFullTextSearch;
        }

        if (!$this->hasSlugColumn()) {
            $this->hasFullTextSearch = false;

            return false;
        }

        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver !== 'mysql') {
            $this->hasFullTextSearch = false;

            return false;
        }

        $stmt = $this->db->prepare("SHOW INDEX FROM {$this->table} WHERE Key_name = 'ft_produtos_search'");
        $stmt->execute();
        $this->hasFullTextSearch = $stmt->fetch() !== false;

        return $this->hasFullTextSearch;
    }

    private function tableExists(string $table): bool
    {
        $driver = (string) $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $stmt = $this->db->prepare(
                "SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = ? LIMIT 1"
            );
            $stmt->execute([$table]);

            return $stmt->fetchColumn() !== false;
        }

        $stmt = $this->db->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1'
        );
        $stmt->execute([$table]);

        return $stmt->fetchColumn() !== false;
    }
}
