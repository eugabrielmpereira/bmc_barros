<?php

declare(strict_types=1);

namespace Erp\Commerce\Repositories;

use Core\Database\Repository;
use PDO;

final class CartItemRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('cart_items', $pdo);
    }

    public function findByCartAndProduct(int $cartId, int $productId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE cart_id = ? AND product_id = ? LIMIT 1");
        $stmt->execute([$cartId, $productId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function listByCart(int $cartId): array
    {
        $sql = "SELECT ci.*, p.nome AS product_name, p.imagem, p.estoque_atual, p.status
                FROM {$this->table} ci
                INNER JOIN produtos p ON p.id = ci.product_id
                WHERE ci.cart_id = ?
                ORDER BY ci.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$cartId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function createItem(int $cartId, int $productId, int $quantity, float $unitPrice): bool
    {
        return $this->create([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
        ]);
    }

    public function updateItem(int $itemId, int $quantity, float $unitPrice): bool
    {
        return $this->update($itemId, [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
        ]);
    }

    public function deleteByCart(int $cartId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE cart_id = ?");
        return $stmt->execute([$cartId]);
    }

    public function deleteByCartAndProduct(int $cartId, int $productId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE cart_id = ? AND product_id = ?");
        return $stmt->execute([$cartId, $productId]);
    }
}
