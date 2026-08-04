<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Categorias do catálogo (admin e site público).
 */
final class CreateCategorias extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS categorias (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nome VARCHAR(120) NOT NULL,
                UNIQUE KEY categorias_nome_unique (nome)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('categorias');
    }
}
