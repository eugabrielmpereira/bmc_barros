<?php

declare(strict_types=1);

namespace Database\Migrations;

final class CreateProductStockAlerts extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS product_stock_alerts (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                product_id INT UNSIGNED NOT NULL,
                email VARCHAR(255) NOT NULL,
                notified_at TIMESTAMP NULL DEFAULT NULL,
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY product_stock_alerts_product_email (product_id, email),
                KEY product_stock_alerts_product_pending (product_id, notified_at),
                CONSTRAINT fk_stock_alerts_product FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('product_stock_alerts');
    }
}
