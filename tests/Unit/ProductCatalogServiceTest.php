<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Products\Repositories\ProductRepository;
use Erp\Products\Services\ProductCatalogService;
use PDO;
use PHPUnit\Framework\TestCase;

final class ProductCatalogServiceTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE categorias (
                id INTEGER PRIMARY KEY,
                parent_id INTEGER NULL,
                nome TEXT NOT NULL,
                slug TEXT NOT NULL,
                description TEXT,
                sort_order INTEGER DEFAULT 0,
                level INTEGER DEFAULT 1,
                status TEXT DEFAULT \'active\',
                meta_title TEXT,
                meta_description TEXT
             );
             CREATE TABLE brands (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                slug TEXT NOT NULL,
                status TEXT DEFAULT \'active\',
                sort_order INTEGER DEFAULT 0
             );
             CREATE TABLE produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                slug TEXT NOT NULL,
                sku TEXT,
                brand_id INTEGER,
                descricao TEXT,
                short_description TEXT,
                long_description TEXT,
                preco REAL,
                imagem TEXT,
                estoque_atual INTEGER DEFAULT 0,
                status TEXT DEFAULT \'ativo\'
             );
             CREATE TABLE stock_reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'active\',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                released_at TEXT NULL
             );'
        );
        $this->pdo->exec(
            "INSERT INTO categorias (id, parent_id, nome, slug, level, status) VALUES
                (1, NULL, 'Material Básico', 'material-basico', 1, 'active'),
                (2, 1, 'Cimentos', 'material-basico-cimentos', 2, 'active'),
                (3, NULL, 'Ferragens', 'ferragens', 1, 'active'),
                (4, 3, 'Pregos', 'ferragens-pregos', 2, 'active')"
        );
        $this->pdo->exec("INSERT INTO brands (id, name, slug, status) VALUES (1, 'Marca Teste', 'marca-teste', 'active')");
        $this->pdo->exec(
            "INSERT INTO produtos (categoria_id, nome, slug, brand_id, descricao, estoque_atual, status)
             VALUES (2, 'Cimento CP II', '1-cimento-cp-ii', NULL, '', 8, 'ativo'),
                    (4, 'Prego 17x21', '2-prego-17x21', 1, '', 2, 'ativo'),
                    (2, 'Produto Inativo', '3-produto-inativo', NULL, '', 5, 'inativo')"
        );
    }

    public function testCatalogListsOnlyActiveProducts(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $data = $service->getCatalogData();

        $this->assertCount(2, $data['produtos']);
        $this->assertSame('', $data['activeCategorySlug']);
        $this->assertNull($data['selectedCategoria']);
        $this->assertSame('1-cimento-cp-ii', $data['produtos'][0]['public_slug']);
        $this->assertSame('Disponível', $data['produtos'][0]['availability_label']);
    }

    public function testCatalogFiltersByCategorySlug(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $data = $service->getCatalogData('ferragens');

        $this->assertSame('ferragens', $data['activeCategorySlug']);
        $this->assertSame('Ferragens', $data['selectedCategoria']['nome']);
        $this->assertCount(1, $data['produtos']);
        $this->assertSame('Prego 17x21', $data['produtos'][0]['nome']);
        $this->assertSame('Últimas unidades', $data['produtos'][0]['availability_label']);
    }

    public function testCatalogFiltersByRootCategoryIncludesChildren(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $data = $service->getCatalogData('material-basico');

        $this->assertCount(1, $data['produtos']);
        $this->assertSame('Cimento CP II', $data['produtos'][0]['nome']);
        $this->assertNotEmpty($data['breadcrumbs']);
    }

    public function testCatalogFiltersByBrandSlug(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $data = $service->getCatalogData('', '', 'marca-teste');

        $this->assertSame('marca-teste', $data['activeBrandSlug']);
        $this->assertCount(1, $data['produtos']);
        $this->assertSame('Prego 17x21', $data['produtos'][0]['nome']);
    }

    public function testCategoryTreeHasChildren(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $data = $service->getCatalogData();

        $this->assertCount(2, $data['categoryTree']);
        $this->assertNotEmpty($data['categoryTree'][0]['children']);
    }

    public function testFindPublicProductUsesActivePublicSlug(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $product = $service->findPublicProduct('2-prego-17x21');

        $this->assertNotNull($product);
        $this->assertSame('Prego 17x21', $product['nome']);
        $this->assertSame('2-prego-17x21', $product['public_slug']);
    }

    public function testFindPublicProductRejectsInactiveProduct(): void
    {
        $service = new ProductCatalogService(new ProductRepository($this->pdo));

        $this->assertNull($service->findPublicProduct('3-produto-inativo'));
    }

    public function testCatalogPaginationLimitsResults(): void
    {
        for ($i = 0; $i < 30; $i++) {
            $this->pdo->exec(
                "INSERT INTO produtos (categoria_id, nome, slug, descricao, estoque_atual, status)
                 VALUES (2, 'Extra {$i}', 'extra-{$i}', '', 1, 'ativo')"
            );
        }

        $service = new ProductCatalogService(new ProductRepository($this->pdo));
        $page1 = $service->getCatalogData('', '', '', 1);
        $page2 = $service->getCatalogData('', '', '', 2);

        $this->assertCount(ProductCatalogService::PER_PAGE, $page1['produtos']);
        $this->assertSame(32, $page1['pagination']['total']);
        $this->assertSame(2, $page1['pagination']['total_pages']);
        $this->assertSame(1, $page1['pagination']['page']);
        $this->assertCount(8, $page2['produtos']);
        $this->assertSame(2, $page2['pagination']['page']);
    }
}
