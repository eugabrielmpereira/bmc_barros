<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Persistência de movimentações de estoque (entrada, saída, ajuste).
 * Integra com `produtos.estoque_atual` na camada de Service (Sprint 4).
 */
class CreateStockMovements extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS stock_movements (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                produto_id INT UNSIGNED NOT NULL,
                type ENUM('entrada', 'saida', 'ajuste') NOT NULL COMMENT 'Tipo da movimentação',
                quantity INT UNSIGNED NOT NULL COMMENT 'Quantidade movimentada (valor positivo)',
                previous_stock INT UNSIGNED NOT NULL,
                new_stock INT UNSIGNED NOT NULL,
                reason VARCHAR(255) NULL DEFAULT NULL,
                user_id INT UNSIGNED NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY stock_movements_produto_created (produto_id, created_at),
                KEY stock_movements_user_id (user_id),
                CONSTRAINT fk_stock_movements_produto FOREIGN KEY (produto_id) REFERENCES produtos (id) ON DELETE RESTRICT,
                CONSTRAINT fk_stock_movements_usuario FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('stock_movements');
    }
}
