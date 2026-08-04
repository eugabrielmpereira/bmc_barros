<?php

declare(strict_types=1);

namespace Erp\Orders\Repositories;

use Core\Database\Repository;
use PDO;

final class OrderItemRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('order_items', $pdo);
    }

    public function createItem(int $orderId, int $productId, int $quantity, float $unitPrice): bool
    {
        return $this->create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByOrder(int $orderId): array
    {
        $sql = "SELECT oi.*, p.nome AS product_name
                FROM {$this->table} oi
                INNER JOIN produtos p ON p.id = oi.product_id
                WHERE oi.order_id = ?
                ORDER BY oi.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByIdForOrder(int $itemId, int $orderId): ?array
    {
        if ($itemId <= 0 || $orderId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? AND order_id = ? LIMIT 1");
        $stmt->execute([$itemId, $orderId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function updateQuantityAndTotal(int $itemId, int $quantity, float $unitPrice): bool
    {
        return $this->update($itemId, [
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_amount' => $quantity * $unitPrice,
        ]);
    }

    public function deleteById(int $itemId): bool
    {
        return $this->delete($itemId);
    }

    public function sumTotalByOrder(int $orderId): float
    {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM {$this->table} WHERE order_id = ?");
        $stmt->execute([$orderId]);

        return (float) $stmt->fetchColumn();
    }
}
