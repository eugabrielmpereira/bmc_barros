<?php

declare(strict_types=1);

namespace Database\Migrations;

final class AddRoleToUsuarios extends Migration
{
    public function up(): void
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'role'");
        if ($stmt !== false && $stmt->fetch() === false) {
            $this->exec(
                "ALTER TABLE usuarios ADD COLUMN role VARCHAR(40) NOT NULL DEFAULT 'admin' AFTER status"
            );
        }
    }

    public function down(): void
    {
        $stmt = $this->pdo->query("SHOW COLUMNS FROM usuarios LIKE 'role'");
        if ($stmt !== false && $stmt->fetch() !== false) {
            $this->exec('ALTER TABLE usuarios DROP COLUMN role');
        }
    }
}
