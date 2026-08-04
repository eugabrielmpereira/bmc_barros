<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Commercial\Repositories\CommercialClientRepository;
use Erp\Commercial\Services\CommercialClientService;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Orders\Services\OrderService;
use Erp\Products\Repositories\ProductRepository;
use Erp\Reports\Services\ReportService;
use Erp\Stock\Repositories\StockMovementRepository;
use Erp\Stock\Services\StockHistoryService;
use Erp\Stock\Services\StockService;
use PDO;
use PHPUnit\Framework\TestCase;
use Tests\Support\InMemorySchema;

final class ReportServiceTest extends TestCase
{
    private ReportService $service;

    protected function setUp(): void
    {
        $pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec(
            'CREATE TABLE categorias (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL
             );
             CREATE TABLE produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                categoria_id INTEGER NOT NULL,
                nome TEXT NOT NULL,
                descricao TEXT,
                preco REAL,
                imagem TEXT,
                estoque_atual INTEGER DEFAULT 0,
                status TEXT DEFAULT \'ativo\'
             );
             CREATE TABLE commercial_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\',
                total_amount REAL NOT NULL DEFAULT 0,
                notes TEXT,
                created_by INTEGER,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
             );
             CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
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
        $pdo->exec("INSERT INTO categorias (id, nome) VALUES (1, 'Geral')");
        $pdo->exec("INSERT INTO produtos (id, categoria_id, nome, preco, estoque_atual, status) VALUES (1, 1, 'Cimento', 50, 2, 'ativo')");
        $pdo->exec("INSERT INTO commercial_clients (id, name, status) VALUES (1, 'Cliente A', 'active')");
        $pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, total_amount, created_at) VALUES
             (1, 1, 'confirmed', 100.00, '2026-05-10 10:00:00'),
             (2, 1, 'draft', 50.00, '2026-05-12 10:00:00'),
             (3, 1, 'confirmed', 200.00, '2026-04-20 10:00:00')"
        );
        $pdo->exec(
            "INSERT INTO stock_movements (produto_id, type, quantity, previous_stock, new_stock, reason, created_at) VALUES
             (1, 'saida', 2, 4, 2, 'Pedido #1 aprovado', '2026-05-10 10:00:00'),
             (1, 'entrada', 5, 2, 7, 'Compra', '2026-04-20 10:00:00')"
        );

        $productRepository = new ProductRepository($pdo);
        $clientRepository = new CommercialClientRepository($pdo);
        $orderRepository = new OrderRepository($pdo);
        $itemRepository = new OrderItemRepository($pdo);
        $movementRepository = new StockMovementRepository($pdo);
        $stockService = new StockService($pdo, $productRepository, $movementRepository);

        $this->service = new ReportService(
            $productRepository,
            new CommercialClientService($clientRepository),
            new OrderService(
                $pdo,
                $orderRepository,
                $itemRepository,
                $clientRepository,
                $productRepository,
                $stockService,
                InMemorySchema::auditService($pdo)
            ),
            new StockHistoryService($movementRepository)
        );
    }

    public function testOperationalSummaryUsesPeriodForOrdersAndStockMovements(): void
    {
        $summary = $this->service->operationalSummary('2026-05-01', '2026-05-31');

        $this->assertSame('2026-05-01', $summary['period']['startDate']);
        $this->assertSame('2026-05-31', $summary['period']['endDate']);
        $this->assertSame(1, $summary['orders']['confirmed']);
        $this->assertSame(1, $summary['orders']['draft']);
        $this->assertSame(100.0, $summary['orders']['totalAmount']);
        $this->assertCount(2, $summary['orderRows']);
        $this->assertCount(1, $summary['stockMovements']);
    }
}
