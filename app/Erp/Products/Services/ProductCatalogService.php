<?php

declare(strict_types=1);

namespace Erp\Products\Services;

use Erp\Products\Repositories\AttributeFacetRepository;
use Erp\Products\Repositories\BrandRepository;
use Erp\Products\Repositories\CategoryRepository;
use Erp\Products\Repositories\ProductRepository;
use Erp\Products\Support\CatalogProductFilter;
use Erp\Stock\Services\StockAvailabilityService;

final class ProductCatalogService
{
    public const PER_PAGE = 24;

    public function __construct(
        private ?ProductRepository $productRepository = null,
        private ?CategoryRepository $categoryRepository = null,
        private ?BrandRepository $brandRepository = null,
        private ?AttributeFacetRepository $attributeFacetRepository = null,
        private ?StockAvailabilityService $stockAvailabilityService = null
    ) {
        $this->productRepository ??= new ProductRepository();
        $pdo = $this->productRepository->getConnection();
        $this->categoryRepository ??= new CategoryRepository($pdo);
        $this->brandRepository ??= new BrandRepository($pdo);
        $this->attributeFacetRepository ??= new AttributeFacetRepository($pdo);
        $this->stockAvailabilityService ??= new StockAvailabilityService($pdo);
    }

    /**
     * @param list<string> $attributeSlugs
     * @return array{
     *     produtos: list<array<string, mixed>>,
     *     categoryTree: list<array<string, mixed>>,
     *     brands: list<array<string, mixed>>,
     *     attributeFacets: list<array<string, mixed>>,
     *     selectedCategoria: array<string, mixed>|null,
     *     selectedBrand: array<string, mixed>|null,
     *     breadcrumbs: list<array{id: int, nome: string, slug: string}>,
     *     activeCategorySlug: string,
     *     activeBrandSlug: string,
     *     activeAttributeSlugs: list<string>,
     *     searchQuery: string,
     *     pagination: array{page: int, per_page: int, total: int, total_pages: int}
     * }
     */
    public function getCatalogData(
        string $categoriaSlug = '',
        string $searchQuery = '',
        string $brandSlug = '',
        int $page = 1,
        array $attributeSlugs = []
    ): array {
        $categoriaSlug = trim($categoriaSlug);
        $searchQuery = trim($searchQuery);
        $brandSlug = trim($brandSlug);
        $page = max(1, $page);

        $selectedCategoria = $categoriaSlug !== ''
            ? $this->categoryRepository->findActiveBySlug($categoriaSlug)
            : null;

        $selectedBrand = null;
        $brandId = null;
        if ($brandSlug !== '') {
            $selectedBrand = $this->brandRepository->findActiveBySlug($brandSlug);
            $brandId = $selectedBrand !== null ? (int) $selectedBrand['id'] : null;
        }

        $attributeSlugs = array_values(array_unique(array_filter(array_map('trim', $attributeSlugs))));
        $attributeOptionIds = $this->attributeFacetRepository->resolveOptionIdsBySlugs($attributeSlugs);
        $activeAttributeSlugs = $this->attributeFacetRepository->slugsByOptionIds($attributeOptionIds);

        $filter = new CatalogProductFilter(
            $selectedCategoria['id'] ?? null,
            $searchQuery,
            $brandId,
            $attributeOptionIds
        );

        $offset = ($page - 1) * self::PER_PAGE;
        $search = $this->productRepository->searchActiveCatalog($filter, self::PER_PAGE, $offset);
        $total = (int) $search['total'];
        $totalPages = $total > 0 ? (int) ceil($total / self::PER_PAGE) : 1;

        if ($page > $totalPages && $total > 0) {
            $page = $totalPages;
            $offset = ($page - 1) * self::PER_PAGE;
            $search = $this->productRepository->searchActiveCatalog($filter, self::PER_PAGE, $offset);
        }

        $breadcrumbs = [];
        if ($selectedCategoria !== null) {
            $breadcrumbs = $this->categoryRepository->getBreadcrumbs((int) $selectedCategoria['id']);
        }

        return [
            'produtos' => $this->enrichProducts($search['items']),
            'categoryTree' => $this->categoryRepository->getCatalogTree(),
            'brands' => $this->brandRepository->listForCatalogFilter(),
            'attributeFacets' => $this->attributeFacetRepository->listForCatalog(),
            'selectedCategoria' => $selectedCategoria,
            'selectedBrand' => $selectedBrand,
            'breadcrumbs' => $breadcrumbs,
            'activeCategorySlug' => $categoriaSlug,
            'activeBrandSlug' => $brandSlug,
            'activeAttributeSlugs' => $activeAttributeSlugs,
            'searchQuery' => $searchQuery,
            'pagination' => [
                'page' => $page,
                'per_page' => self::PER_PAGE,
                'total' => $total,
                'total_pages' => max(1, $totalPages),
            ],
            'categorias' => $this->categoryRepository->getCatalogTree(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublicProduct(string $slug): ?array
    {
        $product = $this->productRepository->findActiveByPublicSlug($slug);
        if ($product === null) {
            return null;
        }

        $product = $this->enrichProduct($product);
        $categoryId = (int) ($product['categoria_id'] ?? 0);
        $product['breadcrumbs'] = $categoryId > 0
            ? $this->categoryRepository->getBreadcrumbs($categoryId)
            : [];

        return $product;
    }

    /**
     * @param list<string> $raw
     * @return list<string>
     */
    public static function parseAttributeSlugsFromRequest(array $raw): array
    {
        $slugs = [];
        foreach ($raw as $value) {
            if (is_array($value)) {
                foreach ($value as $nested) {
                    $nested = trim((string) $nested);
                    if ($nested !== '') {
                        $slugs[] = $nested;
                    }
                }
                continue;
            }
            $value = trim((string) $value);
            if ($value !== '') {
                $slugs[] = $value;
            }
        }

        return array_values(array_unique($slugs));
    }

    /**
     * @param list<array<string, mixed>> $products
     * @return list<array<string, mixed>>
     */
    private function enrichProducts(array $products): array
    {
        return array_map(fn (array $product): array => $this->enrichProduct($product), $products);
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    private function enrichProduct(array $product): array
    {
        $productId = (int) ($product['id'] ?? 0);
        $stock = $productId > 0
            ? $this->stockAvailabilityService->getAvailableQuantity($productId)
            : max(0, (int) ($product['estoque_atual'] ?? 0));
        $product['estoque_atual'] = $stock;

        $product['public_slug'] = $this->productRepository->publicSlug($product);
        $product['availability_status'] = $stock > 0 ? ($stock <= 3 ? 'low_stock' : 'available') : 'on_request';
        $product['availability_label'] = match ($product['availability_status']) {
            'low_stock' => 'Últimas unidades',
            'available' => 'Disponível',
            default => 'Consultar disponibilidade',
        };

        if (!empty($product['short_description'])) {
            $product['descricao'] = (string) $product['short_description'];
        }

        return $product;
    }
}
