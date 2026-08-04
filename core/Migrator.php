<?php

declare(strict_types=1);

namespace Core;

use PDO;

class Migrator
{
    private PDO $pdo;
    private string $migrationPath;

    public function __construct(PDO $pdo, string $migrationPath)
    {
        $this->pdo = $pdo;
        $this->migrationPath = $migrationPath;
        $this->createMigrationsTable();
    }

    private function createMigrationsTable(): void
    {
        $sql = <<<SQL
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                batch INT NOT NULL,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        SQL;

        $this->pdo->exec($sql);
    }

    public function run(): void
    {
        $batch = $this->getNextBatch();
        $migrations = $this->getPendingMigrations();

        if (empty($migrations)) {
            echo "✅ No pending migrations.\n";
            return;
        }

        foreach ($migrations as $migration) {
            $this->runMigration($migration, $batch);
        }

        echo "✅ Migrations completed successfully.\n";
    }

    private function runMigration(string $migration, int $batch): void
    {
        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $migration . '.php';
        if (!is_file($file)) {
            echo "❌ Arquivo de migration não encontrado: {$file}\n";
            return;
        }

        require_once $file;

        $migrationClass = 'Database\\Migrations\\' . $this->getMigrationClassName($migration);

        if (!class_exists($migrationClass)) {
            echo "❌ Migration class not found: $migrationClass\n";
            return;
        }

        $instance = new $migrationClass($this->pdo);
        $instance->up();

        $stmt = $this->pdo->prepare('INSERT INTO migrations (migration, batch) VALUES (?, ?)');
        $stmt->execute([$migration, $batch]);

        echo "✅ Migrated: $migration\n";
    }

    public function rollback(): void
    {
        $lastBatch = $this->getLastBatch();

        if ($lastBatch === 0) {
            echo "❌ Nothing to rollback.\n";
            return;
        }

        $stmt = $this->pdo->prepare('SELECT migration FROM migrations WHERE batch = ? ORDER BY id DESC');
        $stmt->execute([$lastBatch]);
        $migrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($migrations as $migration) {
            $this->rollbackMigration($migration, $lastBatch);
        }

        echo "✅ Rollback completed.\n";
    }

    private function rollbackMigration(string $migration, int $batch): void
    {
        $file = $this->migrationPath . DIRECTORY_SEPARATOR . $migration . '.php';
        if (!is_file($file)) {
            echo "❌ Arquivo de migration não encontrado: {$file}\n";
            return;
        }

        require_once $file;

        $migrationClass = 'Database\\Migrations\\' . $this->getMigrationClassName($migration);

        if (!class_exists($migrationClass)) {
            echo "❌ Migration class not found: $migrationClass\n";
            return;
        }

        $instance = new $migrationClass($this->pdo);
        $instance->down();

        $stmt = $this->pdo->prepare('DELETE FROM migrations WHERE migration = ? AND batch = ?');
        $stmt->execute([$migration, $batch]);

        echo "✅ Rolled back: $migration\n";
    }

    private function getPendingMigrations(): array
    {
        $executed = $this->getExecutedMigrations();
        $pending = [];

        $files = glob($this->migrationPath . '/*.php');
        foreach ($files as $file) {
            $filename = basename($file, '.php');

            // Classe base e arquivos cujo nome não gera classe (ex.: sem underscore) não são migrations.
            if ($filename === 'Migration' || $this->getMigrationClassName($filename) === '') {
                continue;
            }

            if (!in_array($filename, $executed)) {
                $pending[] = $filename;
            }
        }

        sort($pending);
        return $pending;
    }

    private function getExecutedMigrations(): array
    {
        $stmt = $this->pdo->query('SELECT migration FROM migrations');
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?? [];
    }

    private function getNextBatch(): int
    {
        $stmt = $this->pdo->query('SELECT MAX(batch) as batch FROM migrations');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return ($result['batch'] ?? 0) + 1;
    }

    private function getLastBatch(): int
    {
        $stmt = $this->pdo->query('SELECT MAX(batch) as batch FROM migrations');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['batch'] ?? 0;
    }

    public function getMigrationClassName(string $filename): string
    {
        $parts = explode('_', $filename, 2);
        if (count($parts) === 2) {
            return ucfirst(str_replace('_', '', ucwords($parts[1], '_')));
        }
        return '';
    }
}
