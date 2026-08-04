<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\ValidationException;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockMovementRepository;
use Erp\Stock\Services\StockService;
use PDO;
use PHPUnit\Framework\TestCase;

final class StockServiceTest extends TestCase
{
    private PDO $pdo;
    private ProductRepository $productRepository;
    private StockMovementRepository $movementRepository;
    private StockService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                descricao TEXT,
                preco REAL,
                imagem TEXT,
                estoque_atual INTEGER DEFAULT 0,
                status TEXT DEFAULT \'ativo\'
             );
             CREATE TABLE stock_movements (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                produto_id INTEGER NOT NULL,
                type TEXT NOT NULL,
                quantity INTEGER NOT NULL,
                previous_stock INTEGER NOT NULL,
                new_stock INTEGER NOT NULL,
                reason TEXT,
                user_id INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );'
        );
        $this->pdo->exec(
            "INSERT INTO produtos (id, categoria_id, nome, estoque_atual, status)
             VALUES (1, 1, 'Cimento', 10, 'ativo'),
                    (2, 1, 'Areia', 3, 'ativo')"
        );

        $this->productRepository = new ProductRepository($this->pdo);
        $this->movementRepository = new StockMovementRepository($this->pdo);
        $this->service = new StockService($this->pdo, $this->productRepository, $this->movementRepository);
    }

    public function testAddStockUpdatesProductAndCreatesMovement(): void
    {
        $this->service->addStock(1, 5, 9, 'Compra');

        $product = $this->productRepository->find(1);
        $movement = $this->pdo->query('SELECT * FROM stock_movements')->fetch(PDO::FETCH_ASSOC);

        $this->assertSame(15, (int) $product['estoque_atual']);
        $this->assertSame('entrada', $movement['type']);
        $this->assertSame(10, (int) $movement['previous_stock']);
        $this->assertSame(15, (int) $movement['new_stock']);
        $this->assertSame(9, (int) $movement['user_id']);
    }

    public function testRemoveStockRejectsNegativeResult(): void
    {
        $this->expectException(ValidationException::class);

        $this->service->removeStock(1, 11);
    }

    public function testAdjustStockWithoutDeltaDoesNotCreateMovement(): void
    {
        $this->service->adjustStock(1, 10);

        $count = (int) $this->pdo->query('SELECT COUNT(*) FROM stock_movements')->fetchColumn();

        $this->assertSame(0, $count);
    }

    public function testAdjustManyUpdatesChangedProductsAndCreatesMovements(): void
    {
        $result = $this->service->adjustMany([
            1 => 8,
            2 => 6,
        ], 9, 'Inventário rápido');

        $productOne = $this->productRepository->find(1);
        $productTwo = $this->productRepository->find(2);
        $movements = $this->pdo->query('SELECT * FROM stock_movements ORDER BY id ASC')->fetchAll(PDO::FETCH_ASSOC);

        $this->assertSame(['updated' => 2, 'skipped' => 0], $result);
        $this->assertSame(8, (int) $productOne['estoque_atual']);
        $this->assertSame(6, (int) $productTwo['estoque_atual']);
        $this->assertSame('saida', $movements[0]['type']);
        $this->assertSame('entrada', $movements[1]['type']);
    }
}
