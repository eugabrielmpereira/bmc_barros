<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Frete por zona (bairro / cidade / faixa de CEP) + valor de frete e forma de pagamento no pedido.
 */
final class CreateDeliveryZonesAndOrderShipping extends Migration
{
    public function up(): void
    {
        $this->exec(
            "CREATE TABLE IF NOT EXISTS delivery_zones (
                id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                name VARCHAR(120) NOT NULL,
                city VARCHAR(120) NOT NULL,
                state CHAR(2) NOT NULL,
                neighborhoods TEXT NULL DEFAULT NULL,
                postal_code_start CHAR(8) NULL DEFAULT NULL,
                postal_code_end CHAR(8) NULL DEFAULT NULL,
                fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                free_shipping_min DECIMAL(10,2) NULL DEFAULT NULL,
                min_order_amount DECIMAL(10,2) NULL DEFAULT NULL,
                lead_time VARCHAR(80) NULL DEFAULT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY delivery_zones_status_sort (status, sort_order)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->addColumnIfMissing('orders', 'shipping_fee', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER total_amount');
        $this->addColumnIfMissing('orders', 'delivery_zone_id', 'INT UNSIGNED NULL DEFAULT NULL AFTER delivery_method');
        $this->addColumnIfMissing('orders', 'delivery_zone_name', 'VARCHAR(120) NULL DEFAULT NULL AFTER delivery_zone_id');
        $this->addColumnIfMissing('orders', 'payment_method', "VARCHAR(20) NOT NULL DEFAULT 'pix' AFTER delivery_zone_name");
        $this->addColumnIfMissing('orders', 'installments', 'TINYINT UNSIGNED NOT NULL DEFAULT 1 AFTER payment_method');
    }

    public function down(): void
    {
        $this->dropColumnIfExists('orders', 'installments');
        $this->dropColumnIfExists('orders', 'payment_method');
        $this->dropColumnIfExists('orders', 'delivery_zone_name');
        $this->dropColumnIfExists('orders', 'delivery_zone_id');
        $this->dropColumnIfExists('orders', 'shipping_fee');
        $this->dropIfExists('delivery_zones');
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $check = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $this->pdo->quote($column));
        if ($check !== false && $check->fetch() === false) {
            $this->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
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
