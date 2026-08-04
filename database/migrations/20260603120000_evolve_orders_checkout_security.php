<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Checkout público: notas separadas, referência pública, endereço snapshot e ownership.
 */
final class EvolveOrdersCheckoutSecurity extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing('orders', 'customer_notes', 'TEXT NULL DEFAULT NULL AFTER notes');
        $this->addColumnIfMissing('orders', 'internal_notes', 'TEXT NULL DEFAULT NULL AFTER customer_notes');
        $this->addColumnIfMissing('orders', 'public_reference', 'VARCHAR(32) NULL DEFAULT NULL AFTER internal_notes');
        $this->addColumnIfMissing('orders', 'ownership_verified', 'TINYINT(1) NOT NULL DEFAULT 1 AFTER public_reference');
        $this->addColumnIfMissing('orders', 'shipping_postal_code', 'VARCHAR(10) NULL DEFAULT NULL AFTER ownership_verified');
        $this->addColumnIfMissing('orders', 'shipping_street', 'VARCHAR(255) NULL DEFAULT NULL AFTER shipping_postal_code');
        $this->addColumnIfMissing('orders', 'shipping_number', 'VARCHAR(20) NULL DEFAULT NULL AFTER shipping_street');
        $this->addColumnIfMissing('orders', 'shipping_complement', 'VARCHAR(100) NULL DEFAULT NULL AFTER shipping_number');
        $this->addColumnIfMissing('orders', 'shipping_neighborhood', 'VARCHAR(100) NULL DEFAULT NULL AFTER shipping_complement');
        $this->addColumnIfMissing('orders', 'shipping_city', 'VARCHAR(100) NULL DEFAULT NULL AFTER shipping_neighborhood');
        $this->addColumnIfMissing('orders', 'shipping_state', 'CHAR(2) NULL DEFAULT NULL AFTER shipping_city');

        $this->addUniqueIndexIfMissing('orders', 'orders_public_reference_unique', 'public_reference');
    }

    public function down(): void
    {
        $this->dropIndexIfExists('orders', 'orders_public_reference_unique');

        foreach ([
            'shipping_state',
            'shipping_city',
            'shipping_neighborhood',
            'shipping_complement',
            'shipping_number',
            'shipping_street',
            'shipping_postal_code',
            'ownership_verified',
            'public_reference',
            'internal_notes',
            'customer_notes',
        ] as $column) {
            $this->dropColumnIfExists('orders', $column);
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $check = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $this->pdo->quote($column));
        if ($check !== false && $check->fetch() === false) {
            $this->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function addUniqueIndexIfMissing(string $table, string $indexName, string $column): void
    {
        $stmt = $this->pdo->query("SHOW INDEX FROM {$table} WHERE Key_name = " . $this->pdo->quote($indexName));
        if ($stmt !== false && $stmt->fetch() === false) {
            $this->exec("ALTER TABLE {$table} ADD UNIQUE KEY {$indexName} ({$column})");
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
