<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Audit\Repositories\AuditLogRepository;
use Erp\Audit\Services\AuditService;
use Erp\Finance\Services\ReceivableService;
use Erp\Orders\Repositories\OrderRepository;
use Erp\Orders\Services\OrderService;
use PDO;

/**
 * DDL e factories compartilhados para testes unitários com SQLite in-memory.
 */
final class InMemorySchema
{
    public static function createPdo(): PDO
    {
        return new PDO('sqlite::memory:', null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
    }

    public static function auditService(PDO $pdo): AuditService
    {
        return new AuditService(new AuditLogRepository($pdo));
    }

    public static function auditLogs(): string
    {
        return <<<'SQL'
            CREATE TABLE audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                action TEXT NOT NULL,
                entity TEXT NOT NULL,
                entity_id INTEGER,
                user_id INTEGER,
                metadata TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            SQL;
    }

    public static function orderPaymentCharges(): string
    {
        return <<<'SQL'
            CREATE TABLE order_payment_charges (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL UNIQUE,
                provider TEXT NOT NULL,
                external_payment_id TEXT NOT NULL,
                external_customer_id TEXT,
                status TEXT NOT NULL,
                billing_type TEXT NOT NULL,
                amount REAL NOT NULL,
                due_date TEXT NOT NULL,
                pix_copy_paste TEXT,
                pix_qr_image_base64 TEXT,
                invoice_url TEXT,
                paid_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                updated_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            SQL;
    }

    public static function stockReservations(): string
    {
        return <<<'SQL'
            CREATE TABLE stock_reservations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                created_at TEXT DEFAULT CURRENT_TIMESTAMP,
                released_at TEXT NULL
            );
            SQL;
    }

    public static function orderItemsMinimal(): string
    {
        return <<<'SQL'
            CREATE TABLE order_items (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                order_id INTEGER NOT NULL,
                product_id INTEGER NOT NULL,
                quantity INTEGER NOT NULL,
                unit_price REAL NOT NULL DEFAULT 0,
                total_amount REAL NOT NULL DEFAULT 0
            );
            SQL;
    }

    public static function produtosMinimal(): string
    {
        return <<<'SQL'
            CREATE TABLE categorias (
                id INTEGER PRIMARY KEY,
                nome TEXT NOT NULL
            );
            CREATE TABLE produtos (
                id INTEGER PRIMARY KEY,
                categoria_id INTEGER NOT NULL DEFAULT 1,
                nome TEXT NOT NULL,
                estoque_atual INTEGER DEFAULT 0,
                status TEXT DEFAULT 'ativo'
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
            );
            SQL;
    }

    public static function financeWebhookCore(): string
    {
        return self::commercialClientsMinimal() . self::ordersFinanceMinimal()
            . self::orderItemsMinimal()
            . self::produtosMinimal()
            . self::orderPaymentCharges() . self::auditLogs() . self::stockReservations();
    }

    public static function commercialClientsMinimal(): string
    {
        return <<<'SQL'
            CREATE TABLE commercial_clients (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT,
                phone TEXT,
                status TEXT NOT NULL DEFAULT 'active'
            );
            SQL;
    }

    public static function ordersFinanceMinimal(): string
    {
        return <<<'SQL'
            CREATE TABLE orders (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                commercial_client_id INTEGER NOT NULL,
                status TEXT NOT NULL DEFAULT 'draft',
                payment_status TEXT NOT NULL DEFAULT 'not_applicable',
                paid_at TEXT,
                total_amount REAL NOT NULL DEFAULT 0,
                shipping_fee REAL NOT NULL DEFAULT 0,
                public_reference TEXT,
                delivery_method TEXT NOT NULL DEFAULT 'delivery',
                payment_method TEXT NOT NULL DEFAULT 'pix',
                installments INTEGER NOT NULL DEFAULT 1,
                fulfillment_status TEXT NOT NULL DEFAULT 'pending',
                fulfillment_notes TEXT,
                fulfillment_updated_at TEXT,
                created_at TEXT DEFAULT CURRENT_TIMESTAMP
            );
            SQL;
    }

    public static function orderService(
        PDO $pdo,
        AuditService $auditService,
        OrderRepository $orderRepository
    ): OrderService {
        return new OrderService(
            $pdo,
            $orderRepository,
            auditService: $auditService,
            receivableService: new ReceivableService($pdo, $orderRepository, $auditService)
        );
    }
}
