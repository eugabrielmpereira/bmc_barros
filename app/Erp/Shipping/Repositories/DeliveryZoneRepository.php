<?php

declare(strict_types=1);

namespace Erp\Shipping\Repositories;

use Core\Database\Repository;
use PDO;

final class DeliveryZoneRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('delivery_zones', $pdo);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY sort_order ASC, city ASC, name ASC");

        return $stmt !== false ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listActive(): array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE status = 'active' ORDER BY sort_order ASC, id ASC");
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countActive(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table} WHERE status = 'active'");

        return $stmt !== false ? (int) $stmt->fetchColumn() : 0;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createZone(array $data): int
    {
        $this->create($data);

        return (int) $this->db->lastInsertId();
    }
}
