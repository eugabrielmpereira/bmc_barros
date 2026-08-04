<?php

declare(strict_types=1);

namespace Database\Migrations;

final class CreateStockReservations extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS stock_reservations (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                order_id BIGINT UNSIGNED NOT NULL,
                product_id INT UNSIGNED NOT NULL,
                quantity INT UNSIGNED NOT NULL,
                status ENUM('active', 'released', 'consumed') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                released_at TIMESTAMP NULL DEFAULT NULL,
                KEY stock_reservations_order_id (order_id),
                KEY stock_reservations_product_status (product_id, status),
                CONSTRAINT fk_stock_reservations_order FOREIGN KEY (order_id) REFERENCES orders (id) ON DELETE CASCADE,
                CONSTRAINT fk_stock_reservations_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('stock_reservations');
    }
}
