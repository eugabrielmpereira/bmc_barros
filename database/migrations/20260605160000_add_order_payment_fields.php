<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Financeiro básico: status de pagamento e data de baixa em pedidos.
 */
final class AddOrderPaymentFields extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing(
            'orders',
            'payment_status',
            "VARCHAR(20) NOT NULL DEFAULT 'not_applicable' AFTER status"
        );
        $this->addColumnIfMissing(
            'orders',
            'paid_at',
            'TIMESTAMP NULL DEFAULT NULL AFTER payment_status'
        );

        $this->addIndexIfMissing('orders', 'orders_payment_status_created', 'payment_status, created_at');

        $this->exec("UPDATE orders SET payment_status = 'pending' WHERE status = 'confirmed' AND payment_status = 'not_applicable'");
        $this->exec("UPDATE orders SET payment_status = 'cancelled' WHERE status = 'cancelled' AND payment_status = 'not_applicable'");
    }

    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'orders_payment_status_created');
        $this->dropColumnIfExists('orders', 'paid_at');
        $this->dropColumnIfExists('orders', 'payment_status');
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $check = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $this->pdo->quote($column));
        if ($check !== false && $check->fetch() === false) {
            $this->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addIndexIfMissing(string $table, string $indexName, string $columns): void
    {
        $stmt = $this->pdo->query("SHOW INDEX FROM {$table} WHERE Key_name = " . $this->pdo->quote($indexName));
        if ($stmt !== false && $stmt->fetch() === false) {
            $this->exec("ALTER TABLE {$table} ADD KEY {$indexName} ({$columns})");
        }
    }

    private function dropIndexIfExists(string $table, string $indexName): void
    {
        $stmt = $this->pdo->query("SHOW INDEX FROM {$table} WHERE Key_name = " . $this->pdo->quote($indexName));
        if ($stmt !== false && $stmt->fetch() !== false) {
            $this->exec("ALTER TABLE {$table} DROP INDEX {$indexName}");
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $check = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $this->pdo->quote($column));
        if ($check !== false && $check->fetch() !== false) {
            $this->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }
}
