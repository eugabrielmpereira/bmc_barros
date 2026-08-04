<?php

declare(strict_types=1);

namespace Database\Migrations;

final class CreateCommercialClients extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS commercial_clients (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(160) NOT NULL,
                document VARCHAR(40) NULL DEFAULT NULL,
                email VARCHAR(190) NULL DEFAULT NULL,
                phone VARCHAR(40) NULL DEFAULT NULL,
                address VARCHAR(255) NULL DEFAULT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY commercial_clients_status_name (status, name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('commercial_clients');
    }
}
