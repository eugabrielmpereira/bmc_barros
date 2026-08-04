<?php

declare(strict_types=1);

namespace Erp\Customers\Repositories;

use Core\Database\Repository;
use PDO;

final class CustomerRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('customers', $pdo);
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE LOWER(TRIM(email)) = ? LIMIT 1"
        );
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findByCommercialClientId(int $commercialClientId): ?array
    {
        if ($commercialClientId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} WHERE commercial_client_id = ? LIMIT 1"
        );
        $stmt->execute([$commercialClientId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createCustomer(array $data): int
    {
        if (!$this->create($data)) {
            return 0;
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateProfile(int $customerId, array $data): bool
    {
        if ($customerId <= 0) {
            return false;
        }

        $allowed = [];
        foreach (['name', 'phone', 'document'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }
            $allowed[$field] = $value;
        }

        return $allowed === [] ? true : $this->update($customerId, $allowed);
    }
}
