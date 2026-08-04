<?php

declare(strict_types=1);

namespace Erp\Stock\Repositories;

use Core\Database\Repository;
use PDO;

final class StockReservationRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('stock_reservations', $pdo);
    }

    public function sumActiveByProduct(int $productId): int
    {
        if ($productId <= 0) {
            return 0;
        }

        $sql = "SELECT COALESCE(SUM(quantity), 0) FROM {$this->table}
                WHERE product_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);

        return (int) $stmt->fetchColumn();
    }

    public function createReservation(int $orderId, int $productId, int $quantity): void
    {
        $this->create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'status' => 'active',
        ]);
    }

    public function releaseByOrder(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $sql = "UPDATE {$this->table}
                SET status = 'released', released_at = CURRENT_TIMESTAMP
                WHERE order_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
    }

    public function consumeByOrder(int $orderId): void
    {
        if ($orderId <= 0) {
            return;
        }

        $sql = "UPDATE {$this->table}
                SET status = 'consumed', released_at = CURRENT_TIMESTAMP
                WHERE order_id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$orderId]);
    }
}
