<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Alinha tabelas usuarios legadas ao contrato atual dos repositories.
 */
final class AddTimestampsToUsuarios extends Migration
{
    public function up(): void
    {
        $createdAt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'created_at'");
        if ($createdAt !== false && $createdAt->fetch() === false) {
            $this->exec(
                "ALTER TABLE usuarios ADD COLUMN created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER role"
            );
        }

        $updatedAt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'updated_at'");
        if ($updatedAt !== false && $updatedAt->fetch() === false) {
            $this->exec(
                "ALTER TABLE usuarios ADD COLUMN updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at"
            );
        }
    }

    public function down(): void
    {
        $updatedAt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'updated_at'");
        if ($updatedAt !== false && $updatedAt->fetch() !== false) {
            $this->exec('ALTER TABLE usuarios DROP COLUMN updated_at');
        }

        $createdAt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'created_at'");
        if ($createdAt !== false && $createdAt->fetch() !== false) {
            $this->exec('ALTER TABLE usuarios DROP COLUMN created_at');
        }
    }
}
