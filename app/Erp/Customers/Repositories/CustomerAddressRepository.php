<?php

declare(strict_types=1);

namespace Erp\Customers\Repositories;

use Core\Database\Repository;
use PDO;

final class CustomerAddressRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('customer_addresses', $pdo);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listByCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE customer_id = ? ORDER BY is_default DESC, id ASC"
        );
        $stmt->execute([$customerId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findForCustomer(int $addressId, int $customerId): ?array
    {
        if ($addressId <= 0 || $customerId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE id = ? AND customer_id = ? LIMIT 1"
        );
        $stmt->execute([$addressId, $customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findDefault(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE customer_id = ? AND is_default = 1 LIMIT 1"
        );
        $stmt->execute([$customerId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row !== false) {
            return $row;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE customer_id = ? ORDER BY id ASC LIMIT 1"
        );
        $stmt->execute([$customerId]);
        $fallback = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fallback !== false ? $fallback : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createAddress(int $customerId, array $data): int
    {
        if ($customerId <= 0 || !$this->create(array_merge($data, ['customer_id' => $customerId]))) {
            return 0;
        }

        return (int) $this->db->lastInsertId();
    }

    public function clearDefault(int $customerId): void
    {
        if ($customerId <= 0) {
            return;
        }

        $stmt = $this->db->prepare(
            "UPDATE {$this->table} SET is_default = 0 WHERE customer_id = ?"
        );
        $stmt->execute([$customerId]);
    }

    public function setDefault(int $addressId, int $customerId): bool
    {
        if ($addressId <= 0 || $customerId <= 0) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->clearDefault($customerId);
            $updated = $this->update($addressId, ['is_default' => 1]);
            $this->db->commit();

            return $updated;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }
}
