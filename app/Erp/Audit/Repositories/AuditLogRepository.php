<?php

declare(strict_types=1);

namespace Erp\Audit\Repositories;

use Core\Database\Repository;
use PDO;

final class AuditLogRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('audit_logs', $pdo);
    }

    public function log(
        string $action,
        string $entity,
        ?int $entityId,
        ?int $userId,
        array $metadata = []
    ): bool {
        return $this->create([
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'user_id' => $userId,
            'metadata' => $metadata !== [] ? json_encode($metadata, JSON_UNESCAPED_UNICODE) : null,
        ]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listLatest(int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $sql = "SELECT al.*, u.nome AS user_name
                FROM {$this->table} al
                LEFT JOIN usuarios u ON u.id = al.user_id
                ORDER BY al.created_at DESC
                LIMIT ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countAll(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM {$this->table}");

        return (int) $stmt->fetchColumn();
    }

    public function existsActionForEntity(string $action, string $entity, int $entityId): bool
    {
        if ($entityId <= 0) {
            return false;
        }

        $stmt = $this->db->prepare(
            "SELECT 1 FROM {$this->table} WHERE action = ? AND entity = ? AND entity_id = ? LIMIT 1"
        );
        $stmt->execute([$action, $entity, $entityId]);

        return $stmt->fetchColumn() !== false;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findLatestForEntityAction(string $action, string $entity, int $entityId): ?array
    {
        if ($entityId <= 0) {
            return null;
        }

        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table}
             WHERE action = ? AND entity = ? AND entity_id = ?
             ORDER BY id DESC
             LIMIT 1"
        );
        $stmt->execute([$action, $entity, $entityId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
