<?php

declare(strict_types=1);

namespace Database\Migrations;

final class CreateAuditLogs extends Migration
{
    public function up(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS audit_logs (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                action VARCHAR(80) NOT NULL,
                entity VARCHAR(80) NOT NULL,
                entity_id BIGINT UNSIGNED NULL DEFAULT NULL,
                user_id INT UNSIGNED NULL DEFAULT NULL,
                metadata JSON NULL DEFAULT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY audit_logs_entity_created (entity, created_at),
                KEY audit_logs_user_created (user_id, created_at),
                CONSTRAINT fk_audit_logs_usuario FOREIGN KEY (user_id) REFERENCES usuarios (id) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        SQL;

        $this->exec($sql);
    }

    public function down(): void
    {
        $this->dropIfExists('audit_logs');
    }
}
