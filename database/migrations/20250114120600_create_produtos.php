<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Produtos do catálogo (admin, estoque, pedidos e e-commerce).
 */
final class CreateProdutos extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS produtos (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                categoria_id INT UNSIGNED NOT NULL,
                nome VARCHAR(200) NOT NULL,
                descricao TEXT NULL DEFAULT NULL,
                preco DECIMAL(12,2) NULL DEFAULT NULL,
                imagem VARCHAR(255) NULL DEFAULT NULL,
                estoque_atual INT UNSIGNED NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'ativo',
                KEY produtos_categoria_status (categoria_id, status),
                CONSTRAINT fk_produtos_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id) ON DELETE RESTRICT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('produtos');
    }
}
