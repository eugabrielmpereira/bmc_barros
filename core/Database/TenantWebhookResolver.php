<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Exceptions\TenantSuspendedException;
use PDO;

/**
 * Identifica tenant pelo header asaas-access-token (lookup no mestre).
 */
final class TenantWebhookResolver
{
    private const CONNECTABLE_STATUSES = ['active', 'provisioning'];

    /**
     * @return array{
     *     client_config_key: string,
     *     tenant_id: int,
     *     webhook_token: string
     * }|null
     */
    public static function resolveByToken(?string $headerToken): ?array
    {
        $token = trim((string) $headerToken);
        if ($token === '') {
            return null;
        }

        $hash = WebhookTokenHasher::hash($token);
        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            <<<'SQL'
            SELECT
                t.id AS tenant_id,
                t.client_config_key,
                t.status,
                tac.webhook_token
            FROM tenant_asaas_config tac
            INNER JOIN tenants t ON t.id = tac.tenant_id
            WHERE tac.webhook_token_hash = :hash
            LIMIT 1
            SQL
        );
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        $status = (string) ($row['status'] ?? '');
        if (!in_array($status, self::CONNECTABLE_STATUSES, true)) {
            throw new TenantSuspendedException(
                'Tenant do webhook indisponível (status: ' . $status . ').'
            );
        }

        return [
            'client_config_key' => strtolower(trim((string) ($row['client_config_key'] ?? ''))),
            'tenant_id' => (int) ($row['tenant_id'] ?? 0),
            'webhook_token' => (string) ($row['webhook_token'] ?? ''),
        ];
    }
}
