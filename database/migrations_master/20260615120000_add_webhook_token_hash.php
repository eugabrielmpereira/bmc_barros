<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Database\WebhookTokenHasher;

class AddWebhookTokenHash extends Migration
{
    public function up(): void
    {
        $hasColumn = $this->pdo->query(
            "SHOW COLUMNS FROM tenant_asaas_config LIKE 'webhook_token_hash'"
        )->fetch();

        if ($hasColumn === false) {
            $this->exec(
                <<<'SQL'
                ALTER TABLE tenant_asaas_config
                    ADD COLUMN webhook_token_hash CHAR(64) NOT NULL DEFAULT '' AFTER webhook_token,
                    ADD KEY tenant_asaas_config_webhook_token_hash_index (webhook_token_hash)
                SQL
            );
        }

        $this->backfillHashes();
    }

    public function down(): void
    {
        $hasColumn = $this->pdo->query(
            "SHOW COLUMNS FROM tenant_asaas_config LIKE 'webhook_token_hash'"
        )->fetch();

        if ($hasColumn !== false) {
            $this->exec('ALTER TABLE tenant_asaas_config DROP COLUMN webhook_token_hash');
        }
    }

    private function backfillHashes(): void
    {
        $stmt = $this->pdo->query(
            'SELECT tenant_id, webhook_token, webhook_token_hash FROM tenant_asaas_config'
        );
        $rows = $stmt !== false ? $stmt->fetchAll(\PDO::FETCH_ASSOC) : [];

        $update = $this->pdo->prepare(
            'UPDATE tenant_asaas_config SET webhook_token_hash = :hash WHERE tenant_id = :tenant_id'
        );

        foreach ($rows as $row) {
            $existing = trim((string) ($row['webhook_token_hash'] ?? ''));
            if ($existing !== '') {
                continue;
            }

            $plain = trim((string) ($row['webhook_token'] ?? ''));
            if ($plain === '') {
                continue;
            }

            $update->execute([
                'hash' => WebhookTokenHasher::hash($plain),
                'tenant_id' => (int) ($row['tenant_id'] ?? 0),
            ]);
        }
    }
}
