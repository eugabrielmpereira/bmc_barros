<?php

declare(strict_types=1);

namespace Erp\Commercial\Repositories;

use Core\Database\Repository;
use PDO;

final class CommercialClientRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('commercial_clients', $pdo);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForAdmin(string $search = ''): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        $search = trim($search);
        if ($search !== '') {
            $sql .= ' AND (name LIKE ? OR document LIKE ? OR email LIKE ? OR phone LIKE ?)';
            $value = '%' . $search . '%';
            $params = [$value, $value, $value, $value];
        }

        $sql .= ' ORDER BY name ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function activeOptions(): array
    {
        $stmt = $this->db->query(
            "SELECT id, name FROM {$this->table} WHERE status = 'active' ORDER BY name ASC"
        );

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createClient(array $data): int
    {
        if (!$this->create($data)) {
            return 0;
        }

        return (int) $this->db->lastInsertId();
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

    public function findByDocument(string $document): ?array
    {
        $trimmed = trim($document);
        if ($trimmed === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE document = ? LIMIT 1");
        $stmt->execute([$trimmed]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row !== false) {
            return $row;
        }

        $normalized = self::normalizeDocument($trimmed);
        if ($normalized === '') {
            return null;
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table} WHERE document IS NOT NULL AND document <> ''");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: [] as $candidate) {
            if (self::normalizeDocument((string) ($candidate['document'] ?? '')) === $normalized) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Atualiza somente campos seguros após prova de posse no checkout.
     *
     * @param array<string, mixed> $data
     */
    public function updateSafeFields(int $clientId, array $data): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        $update = [];
        foreach (['name', 'phone'] as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }
            $update[$field] = $value;
        }

        return $update === [] ? true : $this->update($clientId, $update);
    }

    /**
     * @deprecated Uso restrito; checkout público deve usar updateSafeFields após ownership gate.
     * @param array<string, mixed> $data
     */
    public function updateClientFromCheckout(int $clientId, array $data): bool
    {
        if ($clientId <= 0) {
            return false;
        }

        $existing = $this->find($clientId);
        if ($existing === null) {
            return false;
        }

        $update = [];
        foreach (['name', 'document', 'email', 'phone', 'address'] as $field) {
            $value = $data[$field] ?? null;
            if ($value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }
            $update[$field] = $value;
        }

        return $update === [] ? true : $this->update($clientId, $update);
    }

    public static function normalizeDocument(string $document): string
    {
        return preg_replace('/\D+/', '', trim($document)) ?? '';
    }

    public function countByStatus(string $status): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE status = ?");
        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }

    public function countCreatedBetween(string $startDateTime, string $endDateTime): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE created_at BETWEEN ? AND ?"
        );
        $stmt->execute([$startDateTime, $endDateTime]);

        return (int) $stmt->fetchColumn();
    }
}
