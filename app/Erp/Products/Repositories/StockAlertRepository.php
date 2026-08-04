<?php

declare(strict_types=1);

namespace Erp\Products\Repositories;

use Core\Database\Repository;
use PDO;

final class StockAlertRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('product_stock_alerts', $pdo);
    }

    public function exists(int $productId, string $email): bool
    {
        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE product_id = ? AND email = ? LIMIT 1"
        );
        $stmt->execute([$productId, strtolower(trim($email))]);

        return (bool) $stmt->fetchColumn();
    }

    public function createAlert(int $productId, string $email): bool
    {
        return $this->create([
            'product_id' => $productId,
            'email' => strtolower(trim($email)),
        ]);
    }
}
