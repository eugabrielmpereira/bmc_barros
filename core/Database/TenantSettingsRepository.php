<?php

declare(strict_types=1);

namespace Core\Database;

use Core\Security\SecretCryptor;
use PDO;

/**
 * Configuração operacional por tenant no banco mestre (Asaas, Mail).
 */
final class TenantSettingsRepository
{
    public function findTenantIdByClientKey(string $clientKey): ?int
    {
        $clientKey = strtolower(trim($clientKey));
        if ($clientKey === '') {
            return null;
        }

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            'SELECT id FROM tenants WHERE client_config_key = :key OR slug = :slug LIMIT 1'
        );
        $stmt->execute(['key' => $clientKey, 'slug' => $clientKey]);
        $id = $stmt->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    /**
     * @return array{
     *     enabled: bool,
     *     sandbox: bool,
     *     api_key: string,
     *     webhook_token: string,
     *     pix_due_days: int
     * }|null
     */
    public function findAsaasByClientKey(string $clientKey): ?array
    {
        $tenantId = $this->findTenantIdByClientKey($clientKey);
        if ($tenantId === null) {
            return null;
        }

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            'SELECT enabled, sandbox, api_key, webhook_token, pix_due_days
             FROM tenant_asaas_config WHERE tenant_id = ? LIMIT 1'
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'enabled' => (bool) ($row['enabled'] ?? false),
            'sandbox' => (bool) ($row['sandbox'] ?? true),
            'api_key' => SecretCryptor::decrypt((string) ($row['api_key'] ?? '')),
            'webhook_token' => (string) ($row['webhook_token'] ?? ''),
            'pix_due_days' => max(1, min((int) ($row['pix_due_days'] ?? 3), 30)),
        ];
    }

    /**
     * @return array{
     *     enabled: bool,
     *     driver: string,
     *     host: string,
     *     port: int,
     *     username: string,
     *     password: string,
     *     encryption: string,
     *     from_address: string,
     *     from_name: string,
     *     admin_to: string
     * }|null
     */
    public function findMailByClientKey(string $clientKey): ?array
    {
        $tenantId = $this->findTenantIdByClientKey($clientKey);
        if ($tenantId === null) {
            return null;
        }

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            'SELECT enabled, driver, host, port, username, password, encryption,
                    from_address, from_name, admin_to
             FROM tenant_mail_config WHERE tenant_id = ? LIMIT 1'
        );
        $stmt->execute([$tenantId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return [
            'enabled' => (bool) ($row['enabled'] ?? false),
            'driver' => (string) ($row['driver'] ?? 'smtp'),
            'host' => (string) ($row['host'] ?? ''),
            'port' => (int) ($row['port'] ?? 2525),
            'username' => (string) ($row['username'] ?? ''),
            'password' => SecretCryptor::decrypt((string) ($row['password'] ?? '')),
            'encryption' => strtolower((string) ($row['encryption'] ?? 'tls')),
            'from_address' => (string) ($row['from_address'] ?? ''),
            'from_name' => (string) ($row['from_name'] ?? ''),
            'admin_to' => (string) ($row['admin_to'] ?? ''),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsertAsaas(int $tenantId, array $data): void
    {
        $webhookToken = trim((string) ($data['webhook_token'] ?? ''));
        $apiKey = SecretCryptor::encryptIfPossible(trim((string) ($data['api_key'] ?? '')));

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            <<<'SQL'
            INSERT INTO tenant_asaas_config
                (tenant_id, enabled, sandbox, api_key, webhook_token, webhook_token_hash, pix_due_days)
            VALUES
                (:tenant_id, :enabled, :sandbox, :api_key, :webhook_token, :webhook_token_hash, :pix_due_days)
            ON DUPLICATE KEY UPDATE
                enabled = VALUES(enabled),
                sandbox = VALUES(sandbox),
                api_key = VALUES(api_key),
                webhook_token = VALUES(webhook_token),
                webhook_token_hash = VALUES(webhook_token_hash),
                pix_due_days = VALUES(pix_due_days),
                updated_at = CURRENT_TIMESTAMP
            SQL
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'enabled' => filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0,
            'sandbox' => filter_var($data['sandbox'] ?? true, FILTER_VALIDATE_BOOL) ? 1 : 0,
            'api_key' => $apiKey,
            'webhook_token' => $webhookToken,
            'webhook_token_hash' => $webhookToken !== '' ? WebhookTokenHasher::hash($webhookToken) : '',
            'pix_due_days' => max(1, min((int) ($data['pix_due_days'] ?? 3), 30)),
        ]);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function upsertMail(int $tenantId, array $data): void
    {
        $password = SecretCryptor::encryptIfPossible(trim((string) ($data['password'] ?? '')));

        $master = MasterDatabase::getConnection();
        $stmt = $master->prepare(
            <<<'SQL'
            INSERT INTO tenant_mail_config
                (tenant_id, enabled, driver, host, port, username, password, encryption,
                 from_address, from_name, admin_to)
            VALUES
                (:tenant_id, :enabled, :driver, :host, :port, :username, :password, :encryption,
                 :from_address, :from_name, :admin_to)
            ON DUPLICATE KEY UPDATE
                enabled = VALUES(enabled),
                driver = VALUES(driver),
                host = VALUES(host),
                port = VALUES(port),
                username = VALUES(username),
                password = VALUES(password),
                encryption = VALUES(encryption),
                from_address = VALUES(from_address),
                from_name = VALUES(from_name),
                admin_to = VALUES(admin_to),
                updated_at = CURRENT_TIMESTAMP
            SQL
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'enabled' => filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOL) ? 1 : 0,
            'driver' => trim((string) ($data['driver'] ?? 'smtp')),
            'host' => trim((string) ($data['host'] ?? '')),
            'port' => (int) ($data['port'] ?? 2525),
            'username' => trim((string) ($data['username'] ?? '')),
            'password' => $password,
            'encryption' => strtolower(trim((string) ($data['encryption'] ?? 'tls'))),
            'from_address' => trim((string) ($data['from_address'] ?? '')),
            'from_name' => trim((string) ($data['from_name'] ?? '')),
            'admin_to' => trim((string) ($data['admin_to'] ?? '')),
        ]);
    }
}
