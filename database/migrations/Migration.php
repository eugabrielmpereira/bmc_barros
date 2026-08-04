<?php

declare(strict_types=1);

namespace Database\Migrations;

use PDO;

abstract class Migration
{
    protected PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract public function up(): void;
    abstract public function down(): void;

    protected function table(string $name): void
    {
        // Placeholder for future table builder
    }

    protected function dropIfExists(string $table): void
    {
        $this->pdo->exec("DROP TABLE IF EXISTS `$table`");
    }

    protected function exec(string $sql): void
    {
        $this->pdo->exec($sql);
    }
}
