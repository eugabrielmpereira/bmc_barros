<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Sprint 7 — logística: forma de entrega e status operacional de expedição.
 */
final class AddOrderFulfillmentFields extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing(
            'orders',
            'delivery_method',
            "VARCHAR(20) NOT NULL DEFAULT 'delivery' AFTER shipping_state"
        );
        $this->addColumnIfMissing(
            'orders',
            'fulfillment_status',
            "VARCHAR(30) NOT NULL DEFAULT 'pending' AFTER delivery_method"
        );
        $this->addColumnIfMissing(
            'orders',
            'fulfillment_notes',
            'TEXT NULL DEFAULT NULL AFTER fulfillment_status'
        );
        $this->addColumnIfMissing(
            'orders',
            'fulfillment_updated_at',
            'TIMESTAMP NULL DEFAULT NULL AFTER fulfillment_notes'
        );

        $this->addIndexIfMissing('orders', 'orders_fulfillment_status_created', 'fulfillment_status, created_at');

        $this->exec("UPDATE orders SET fulfillment_status = 'cancelled' WHERE status = 'cancelled' AND fulfillment_status = 'pending'");
    }

    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'orders_fulfillment_status_created');
        $this->dropColumnIfExists('orders', 'fulfillment_updated_at');
        $this->dropColumnIfExists('orders', 'fulfillment_notes');
        $this->dropColumnIfExists('orders', 'fulfillment_status');
        $this->dropColumnIfExists('orders', 'delivery_method');
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
