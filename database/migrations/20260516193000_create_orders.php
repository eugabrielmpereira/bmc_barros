<?php

declare(strict_types=1);

namespace Database\Migrations;

final class CreateOrders extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS orders (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                commercial_client_id BIGINT UNSIGNED NOT NULL,
                status VARCHAR(30) NOT NULL DEFAULT 'draft',
                total_amount DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                notes TEXT NULL DEFAULT NULL,
                created_by INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY orders_client_created (commercial_client_id, created_at),
                KEY orders_status_created (status, created_at),
                CONSTRAINT fk_orders_commercial_client FOREIGN KEY (commercial_client_id) REFERENCES commercial_clients (id) ON DELETE RESTRICT,
                CONSTRAINT fk_orders_created_by FOREIGN KEY (created_by) REFERENCES usuarios (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('orders');
    }
}
