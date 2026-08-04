<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Schema do banco mestre (erp_master) — registro operacional multi-tenant.
 */
class CreateMasterTenantTables extends Migration
{
    public function up(): void
    {
        $this->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS tenants (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(64) NOT NULL,
                client_config_key VARCHAR(64) NOT NULL,
                display_name VARCHAR(255) NOT NULL,
                status ENUM('provisioning', 'active', 'suspended', 'archived') NOT NULL DEFAULT 'provisioning',
                suspension_reason VARCHAR(255) NULL,
                suspended_at DATETIME NULL,
                notes TEXT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY tenants_slug_unique (slug),
                UNIQUE KEY tenants_client_config_key_unique (client_config_key)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $this->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS tenant_domains (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                tenant_id BIGINT UNSIGNED NOT NULL,
                host VARCHAR(255) NOT NULL,
                is_primary TINYINT(1) NOT NULL DEFAULT 0,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY tenant_domains_host_unique (host),
                KEY tenant_domains_tenant_id_index (tenant_id),
                CONSTRAINT tenant_domains_tenant_id_foreign
                    FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $this->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS tenant_database (
                tenant_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                database_name VARCHAR(64) NOT NULL,
                charset VARCHAR(32) NOT NULL DEFAULT 'utf8mb4',
                provisioned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                last_migration_at DATETIME NULL,
                UNIQUE KEY tenant_database_name_unique (database_name),
                CONSTRAINT tenant_database_tenant_id_foreign
                    FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $this->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS tenant_asaas_config (
                tenant_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                enabled TINYINT(1) NOT NULL DEFAULT 0,
                sandbox TINYINT(1) NOT NULL DEFAULT 1,
                api_key VARCHAR(512) NOT NULL DEFAULT '',
                webhook_token VARCHAR(512) NOT NULL DEFAULT '',
                pix_due_days TINYINT UNSIGNED NOT NULL DEFAULT 3,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT tenant_asaas_config_tenant_id_foreign
                    FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );

        $this->exec(
            <<<'SQL'
            CREATE TABLE IF NOT EXISTS tenant_mail_config (
                tenant_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
                enabled TINYINT(1) NOT NULL DEFAULT 0,
                driver VARCHAR(32) NOT NULL DEFAULT 'smtp',
                host VARCHAR(255) NOT NULL DEFAULT '',
                port SMALLINT UNSIGNED NOT NULL DEFAULT 2525,
                username VARCHAR(255) NOT NULL DEFAULT '',
                password VARCHAR(512) NOT NULL DEFAULT '',
                encryption VARCHAR(16) NOT NULL DEFAULT 'tls',
                from_address VARCHAR(255) NOT NULL DEFAULT '',
                from_name VARCHAR(255) NOT NULL DEFAULT '',
                admin_to VARCHAR(255) NULL,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                CONSTRAINT tenant_mail_config_tenant_id_foreign
                    FOREIGN KEY (tenant_id) REFERENCES tenants (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL
        );
    }

    public function down(): void
    {
        $this->dropIfExists('tenant_mail_config');
        $this->dropIfExists('tenant_asaas_config');
        $this->dropIfExists('tenant_database');
        $this->dropIfExists('tenant_domains');
        $this->dropIfExists('tenants');
    }
}
