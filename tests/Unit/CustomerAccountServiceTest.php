<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Customers\Repositories\CustomerAddressRepository;
use Erp\Customers\Repositories\CustomerRepository;
use Erp\Customers\Services\CustomerAccountService;
use Erp\Orders\Repositories\OrderItemRepository;
use Erp\Orders\Repositories\OrderRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CustomerAccountServiceTest extends TestCase
{
    private PDO $pdo;
    private CustomerAccountService $service;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        $this->pdo->exec(
            'CREATE TABLE commercial_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password_hash TEXT NOT NULL,
                name TEXT NOT NULL,
                phone TEXT,
                document TEXT,
                status TEXT NOT NULL DEFAULT \'active\'
             );
             CREATE TABLE customer_addresses (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                label TEXT NOT NULL DEFAULT \'Principal\',
                postal_code TEXT,
                street TEXT,
                street_number TEXT,
                complement TEXT,
                neighborhood TEXT,
                city TEXT,
                state TEXT,
                is_default INTEGER NOT NULL DEFAULT 0
             );
             CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT \'draft\',
                total_amount REAL NOT NULL DEFAULT 0,
                public_reference TEXT,
                shipping_city TEXT,
                shipping_state TEXT,
                shipping_street TEXT,
                shipping_number TEXT,
                shipping_neighborhood TEXT,
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

        $this->pdo->exec("INSERT INTO commercial_clients (id, name) VALUES (1, 'Cliente')");
        $this->pdo->exec(
            "INSERT INTO customers (id, commercial_client_id, email, password_hash, name, phone, document)
             VALUES (1, 1, 'cliente@example.com', 'hash', 'Cliente Site', '(11) 98888-1234', '39053344705')"
        );
        $this->pdo->exec(
            "INSERT INTO orders (id, commercial_client_id, status, total_amount, public_reference, shipping_city, shipping_state)
             VALUES (10, 1, 'draft', 99.90, 'TST-20260605-ABC123', 'São Paulo', 'SP')"
        );
        $this->pdo->exec("INSERT INTO produtos (id, nome) VALUES (1, 'Produto')");
        $this->pdo->exec("INSERT INTO order_items (order_id, product_id, quantity, unit_price, total_amount) VALUES (10, 1, 1, 99.90, 99.90)");

        $this->service = new CustomerAccountService(
            new CustomerRepository($this->pdo),
            new CustomerAddressRepository($this->pdo),
            new OrderRepository($this->pdo),
            new OrderItemRepository($this->pdo)
        );
    }

    public function testListOrdersForCustomer(): void
    {
        $orders = $this->service->listOrders(1);

        $this->assertCount(1, $orders);
        $this->assertSame('TST-20260605-ABC123', $orders[0]['public_reference']);
    }

    public function testGetOrderForCustomerByReference(): void
    {
        $order = $this->service->getOrderForCustomer(1, 'TST-20260605-ABC123');

        $this->assertNotNull($order);
        $this->assertSame('Aguardando confirmação da loja', $order['status_label']);
        $this->assertCount(1, $order['items']);
    }

    public function testGetCheckoutPrefillUsesDefaultAddress(): void
    {
        $this->pdo->exec(
            "INSERT INTO customer_addresses (customer_id, label, postal_code, street, street_number, neighborhood, city, state, is_default)
             VALUES (1, 'Casa', '01310-100', 'Av. Paulista', '1000', 'Bela Vista', 'São Paulo', 'SP', 1)"
        );

        $prefill = $this->service->getCheckoutPrefill(1);

        $this->assertSame('Cliente Site', $prefill['name']);
        $this->assertSame('Av. Paulista', $prefill['shipping_street']);
        $this->assertSame('SP', $prefill['shipping_state']);
    }
}
