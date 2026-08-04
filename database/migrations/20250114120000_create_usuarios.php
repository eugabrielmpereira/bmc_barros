<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

class CreateUsuarios extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS usuarios (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(120) NOT NULL,
                email VARCHAR(190) NOT NULL,
                senha VARCHAR(255) NOT NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'ativo',
                created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY usuarios_email_unique (email)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('usuarios');
    }
}
