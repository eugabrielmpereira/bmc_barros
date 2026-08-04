<?php

declare(strict_types=1);

namespace Erp\Users\Repositories;

use Core\Database\Repository;
use PDO;

final class UserRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('usuarios', $pdo);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        $sql = "SELECT id, nome, email, status, role, created_at, updated_at
                FROM {$this->table}
                WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (nome LIKE ? OR email LIKE ?)';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY nome ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = ? LIMIT 1");
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function emailExists(string $email, ?int $ignoreId = null): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE email = ?";
        $params = [trim($email)];

        if ($ignoreId !== null) {
            $sql .= ' AND id <> ?';
            $params[] = $ignoreId;
        }

        $sql .= ' LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchColumn() !== false;
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }
}
