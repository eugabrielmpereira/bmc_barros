<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Products\Repositories\ProductRepository;
use Erp\Stock\Repositories\StockReservationRepository;
use Erp\Stock\Services\StockAvailabilityService;
use Erp\Stock\Services\StockReservationService;
use PDO;
use PHPUnit\Framework\TestCase;

final class StockReservationServiceTest extends TestCase
{
    private PDO $pdo;
    private StockReservationService $service;
    private StockAvailabilityService $availability;

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
                total_amount REAL NOT NULL DEFAULT 0
             );
             CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0
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
            "INSERT INTO produtos (id, categoria_id, nome, estoque_atual, status) VALUES (1, 1, 'Cimento', 10, 'ativo');
             INSERT INTO commercial_clients (id, name, status) VALUES (1, 'Cliente', 'active');
             INSERT INTO orders (id, commercial_client_id, status, total_amount) VALUES (1, 1, 'draft', 50);
             INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_amount) VALUES (1, 1, 4, 12.5, 50);"
        );

        $productRepository = new ProductRepository($this->pdo);
        $reservationRepository = new StockReservationRepository($this->pdo);
        $this->availability = new StockAvailabilityService($this->pdo, $productRepository, $reservationRepository);
        $this->service = new StockReservationService(
            $this->pdo,
            new OrderRepository($this->pdo),
            new OrderItemRepository($this->pdo),
            $productRepository,
            $reservationRepository,
            $this->availability
        );
    }

    public function testSyncForDraftOrderCreatesActiveReservation(): void
    {
        $this->service->syncForDraftOrder(1);

        $this->assertSame(6, $this->availability->getAvailableQuantity(1));
        $reserved = (int) $this->pdo->query("SELECT SUM(quantity) FROM stock_reservations WHERE status = 'active'")->fetchColumn();
        $this->assertSame(4, $reserved);
    }

    public function testReleaseByOrderFreesAvailableStock(): void
    {
        $this->service->syncForDraftOrder(1);
        $this->service->releaseByOrder(1);

        $this->assertSame(10, $this->availability->getAvailableQuantity(1));
        $active = (int) $this->pdo->query("SELECT COUNT(*) FROM stock_reservations WHERE status = 'active'")->fetchColumn();
        $this->assertSame(0, $active);
    }

    public function testSecondDraftOrderCannotReserveMoreThanAvailable(): void
    {
        $this->service->syncForDraftOrder(1);

        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, total_amount) VALUES (2, 1, 'draft', 42);
             INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_amount) VALUES (2, 1, 7, 6, 42);"
        );

        $this->expectException(\Core\Exceptions\ValidationException::class);
        $this->service->syncForDraftOrder(2);
    }
}
