<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Commerce\Repositories\CartItemRepository;
use Erp\Commerce\Repositories\CartRepository;
use Erp\Commerce\Services\CartService;
use Erp\Products\Repositories\ProductRepository;
use PDO;
use PHPUnit\Framework\TestCase;

final class CartServiceTest extends TestCase
{
    private PDO $pdo;
    private CartService $service;

    protected function setUp(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];
        $_COOKIE = [];

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
             );'
        );

        $this->pdo->exec(
            'CREATE TABLE carts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NULL,
                session_token VARCHAR(64) NULL UNIQUE,
                status VARCHAR(20) NOT NULL DEFAULT \'open\',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
             );'
        );

        $this->pdo->exec(
            'CREATE TABLE cart_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                cart_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL DEFAULT 1,
                unit_price REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
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
            "INSERT INTO produtos (id, categoria_id, nome, preco, estoque_atual, status)
             VALUES (1, 1, 'Cimento', 39.90, 10, 'ativo'),
                    (2, 1, 'Inativo', 10.00, 3, 'inativo')"
        );

        $this->service = new CartService(
            new ProductRepository($this->pdo),
            new CartRepository($this->pdo),
            new CartItemRepository($this->pdo)
        );
    }

    public function testAddProductCreatesPersistentCartItem(): void
    {
        $result = $this->service->addProduct(1, 2);
        $summary = $this->service->summary();

        $this->assertTrue($result['success']);
        $this->assertSame(2, $summary['item_count']);
        $this->assertEqualsWithDelta(79.8, $summary['subtotal'], 0.001);
        $this->assertSame('Cimento', $summary['items'][0]['name']);
        $this->assertArrayHasKey('cart_token', $_COOKIE);
    }

    public function testAddProductRejectsInactiveProduct(): void
    {
        $result = $this->service->addProduct(2, 1);

        $this->assertFalse($result['success']);
        $this->assertSame([], $this->service->summary()['items']);
    }

    public function testUpdateQuantitiesRemovesZeroQuantity(): void
    {
        $this->service->addProduct(1, 2);
        $this->service->updateQuantities([1 => 0]);

        $this->assertSame(0, $this->service->summary()['item_count']);
    }

    public function testNewCartAfterFinalizeDoesNotReuseOrderedSessionToken(): void
    {
        $staleToken = '04789db81e5dab2caabb64c25d61508a';
        $_COOKIE['cart_token'] = $staleToken;

        $this->service->addProduct(1, 1);
        $this->service->finalizeCurrentCart();

        $ordered = $this->pdo->query('SELECT session_token, status FROM carts LIMIT 1')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('ordered', $ordered['status']);
        $this->assertNull($ordered['session_token']);

        $_COOKIE['cart_token'] = $staleToken;
        $result = $this->service->addProduct(1, 1);

        $this->assertTrue($result['success']);
        $this->assertSame(1, $this->service->summary()['item_count']);
        $statuses = $this->pdo->query('SELECT status FROM carts ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame(['ordered', 'open'], $statuses);
    }
}
