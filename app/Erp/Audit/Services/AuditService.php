<?php

declare(strict_types=1);

namespace Erp\Audit\Services;

use Erp\Audit\Repositories\AuditLogRepository;
use Throwable;

final class AuditService
{
    public function __construct(
        private ?AuditLogRepository $repository = null
    ) {
        $this->repository ??= new AuditLogRepository();
    }

    public function record(string $action, string $entity, ?int $entityId = null, array $metadata = []): void
    {
        try {
            $this->repository->log($action, $entity, $entityId, $this->currentUserId(), $metadata);
        } catch (Throwable $e) {
            error_log('[Audit] ' . $e->getMessage());
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function latest(int $limit = 50): array
    {
        return $this->repository->listLatest($limit);
    }

    public function total(): int
    {
        return $this->repository->countAll();
    }

    public function hasActionForEntity(string $action, string $entity, int $entityId): bool
    {
        return $this->repository->existsActionForEntity($action, $entity, $entityId);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function latestActionForEntity(string $action, string $entity, int $entityId): ?array
    {
        return $this->repository->findLatestForEntityAction($action, $entity, $entityId);
    }

    private function currentUserId(): ?int
    {
        $id = (int) ($_SESSION['admin_id'] ?? 0);

        return $id > 0 ? $id : null;
    }
}
