<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Alinha tabelas usuarios antigas ao schema atual (coluna status).
 */
class AddStatusUsuarios extends Migration
{
    public function up(): void
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'status'");
        if ($stmt !== false && $stmt->fetch() === false) {
            $this->exec(
                "ALTER TABLE usuarios ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ativo'"
            );
        }
    }

    public function down(): void
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'status'");
        if ($stmt !== false && $stmt->fetch() !== false) {
            $this->exec('ALTER TABLE usuarios DROP COLUMN status');
        }
    }
}
