<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Orders\Services\OrderTrackingService;
use PDO;
use PHPUnit\Framework\TestCase;

final class OrderTrackingServiceTest extends TestCase
{
    private PDO $pdo;
    private OrderTrackingService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $this->pdo->exec(
            'CREATE TABLE commercial_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone TEXT,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\',
                total_amount REAL NOT NULL DEFAULT 0,
                public_reference TEXT UNIQUE,
                shipping_city TEXT,
                shipping_state TEXT,
                delivery_method TEXT NOT NULL DEFAULT \'delivery\',
                fulfillment_status TEXT NOT NULL DEFAULT \'pending\',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
             );
             CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0
             );
             CREATE TABLE produtos (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                nome TEXT NOT NULL
             );'
        );
        $this->pdo->exec("INSERT INTO commercial_clients (id, name, phone) VALUES (1, 'Cliente', '(11) 98888-1234')");
        $this->pdo->exec("INSERT INTO orders (id, commercial_client_id, status, total_amount, public_reference, shipping_city, shipping_state)
                          VALUES (10, 1, 'draft', 50.0, 'TST-20260604-ABC123', 'São Paulo', 'SP')");
        $this->pdo->exec("INSERT INTO produtos (id, nome) VALUES (1, 'Cimento')");
        $this->pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_amount) VALUES (10, 1, 2, 25.0, 50.0)");

        $this->service = new OrderTrackingService(
            new OrderRepository($this->pdo),
            new OrderItemRepository($this->pdo)
        );
    }

    public function testLookupReturnsOrderWhenReferenceAndPhoneMatch(): void
    {
        $result = $this->service->lookup('TST-20260604-ABC123', '1234', '127.0.0.1');

        $this->assertTrue($result['success']);
        $this->assertSame('TST-20260604-ABC123', $result['result']['public_reference']);
        $this->assertSame('Aguardando confirmação da loja', $result['result']['status_label']);
        $this->assertSame('delivery', $result['result']['delivery_method']);
        $this->assertCount(1, $result['result']['items']);
    }

    public function testLookupRejectsWrongPhoneSuffix(): void
    {
        $result = $this->service->lookup('TST-20260604-ABC123', '9999', '127.0.0.1');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('não encontrado', strtolower($result['message']));
    }

    public function testLookupRejectsUnknownReference(): void
    {
        $result = $this->service->lookup('INVALID-REF', '1234', '127.0.0.1');

        $this->assertFalse($result['success']);
    }
}
