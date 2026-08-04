<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

/**
 * Integridade referencial do carrinho e tipos alinhados com produtos.
 */
final class FixCartItemsRelations extends Migration
{
    public function up(): void
    {
        $orphans = $this->pdo->query(
            'SELECT ci.id FROM cart_items ci
             LEFT JOIN carts c ON c.id = ci.cart_id
             WHERE c.id IS NULL'
        )->fetchAll(PDO::FETCH_COLUMN);
        if ($orphans !== []) {
            $ids = implode(',', array_map('intval', $orphans));
            $this->pdo->exec("DELETE FROM cart_items WHERE id IN ({$ids})");
        }

        $orphanProducts = $this->pdo->query(
            'SELECT ci.id FROM cart_items ci
             LEFT JOIN produtos p ON p.id = ci.product_id
             WHERE p.id IS NULL'
        )->fetchAll(PDO::FETCH_COLUMN);
        if ($orphanProducts !== []) {
            $ids = implode(',', array_map('intval', $orphanProducts));
            $this->pdo->exec("DELETE FROM cart_items WHERE id IN ({$ids})");
        }

        if ($this->foreignKeyExists('cart_items', 'fk_cart_items_cart')) {
            $this->exec('ALTER TABLE cart_items DROP FOREIGN KEY fk_cart_items_cart');
        }
        if ($this->foreignKeyExists('cart_items', 'fk_cart_items_product')) {
            $this->exec('ALTER TABLE cart_items DROP FOREIGN KEY fk_cart_items_product');
        }

        $this->exec('ALTER TABLE cart_items MODIFY COLUMN product_id INT UNSIGNED NOT NULL');

        if (!$this->foreignKeyExists('cart_items', 'fk_cart_items_cart')) {
            $this->exec(<<<'SQL'
                ALTER TABLE cart_items
                    ADD CONSTRAINT fk_cart_items_cart
                    FOREIGN KEY (cart_id) REFERENCES carts (id) ON DELETE CASCADE
            SQL);
        }

        if (!$this->foreignKeyExists('cart_items', 'fk_cart_items_product')) {
            $this->exec(<<<'SQL'
                ALTER TABLE cart_items
                    ADD CONSTRAINT fk_cart_items_product
                    FOREIGN KEY (product_id) REFERENCES produtos (id) ON DELETE RESTRICT
            SQL);
        }
    }

    public function down(): void
    {
        if ($this->foreignKeyExists('cart_items', 'fk_cart_items_product')) {
            $this->exec('ALTER TABLE cart_items DROP FOREIGN KEY fk_cart_items_product');
        }
        if ($this->foreignKeyExists('cart_items', 'fk_cart_items_cart')) {
            $this->exec('ALTER TABLE cart_items DROP FOREIGN KEY fk_cart_items_cart');
        }

        $this->exec('ALTER TABLE cart_items MODIFY COLUMN product_id BIGINT UNSIGNED NOT NULL');
    }

    private function foreignKeyExists(string $table, string $name): bool
    {
        $stmt = $this->pdo->prepare(
            'SELECT 1 FROM information_schema.table_constraints
             WHERE table_schema = DATABASE() AND table_name = ? AND constraint_name = ? LIMIT 1'
        );
        $stmt->execute([$table, $name]);

        return $stmt->fetchColumn() !== false;
    }
}
