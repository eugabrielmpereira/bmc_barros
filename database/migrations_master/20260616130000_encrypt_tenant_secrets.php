<?php

declare(strict_types=1);

namespace Database\Migrations;

use Core\Security\SecretCryptor;

/**
 * Criptografa api_key e mail password existentes quando APP_KEY está definida.
 */
class EncryptTenantSecrets extends Migration
{
    public function up(): void
    {
        if (SecretCryptor::appKey() === '') {
            echo "⚠️  APP_KEY ausente — segredos permanecem em texto plano até definir APP_KEY e reexecutar.\n";

            return;
        }

        $this->encryptColumn('tenant_asaas_config', 'api_key');
        $this->encryptColumn('tenant_mail_config', 'password');
    }

    public function down(): void
    {
        // Irreversível sem expor segredos em log — rollback manual se necessário.
    }

    private function encryptColumn(string $table, string $column): void
    {
        $stmt = $this->pdo->query("SELECT tenant_id, {$column} FROM {$table}");
        if ($stmt === false) {
            return;
        }

        $update = $this->pdo->prepare("UPDATE {$table} SET {$column} = :value WHERE tenant_id = :tenant_id");

        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $plain = trim((string) ($row[$column] ?? ''));
            if ($plain === '' || SecretCryptor::isEncrypted($plain)) {
                continue;
            }

            $update->execute([
                'value' => SecretCryptor::encrypt($plain),
                'tenant_id' => (int) ($row['tenant_id'] ?? 0),
            ]);
        }
    }
}
