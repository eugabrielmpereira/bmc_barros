<?php

declare(strict_types=1);

namespace Database\Migrations;

/**
 * Endereço estruturado em clientes comerciais (preparação para checkout com CEP).
 */
final class EvolveCommercialClientsAddress extends Migration
{
    public function up(): void
    {
        $this->addColumnIfMissing('commercial_clients', 'postal_code', 'VARCHAR(10) NULL DEFAULT NULL AFTER address');
        $this->addColumnIfMissing('commercial_clients', 'street', 'VARCHAR(255) NULL DEFAULT NULL AFTER postal_code');
        $this->addColumnIfMissing('commercial_clients', 'street_number', 'VARCHAR(20) NULL DEFAULT NULL AFTER street');
        $this->addColumnIfMissing('commercial_clients', 'complement', 'VARCHAR(100) NULL DEFAULT NULL AFTER street_number');
        $this->addColumnIfMissing('commercial_clients', 'neighborhood', 'VARCHAR(100) NULL DEFAULT NULL AFTER complement');
        $this->addColumnIfMissing('commercial_clients', 'city', 'VARCHAR(100) NULL DEFAULT NULL AFTER neighborhood');
        $this->addColumnIfMissing('commercial_clients', 'state', 'CHAR(2) NULL DEFAULT NULL AFTER city');
    }

    public function down(): void
    {
        foreach (['state', 'city', 'neighborhood', 'complement', 'street_number', 'street', 'postal_code'] as $column) {
            $this->dropColumnIfExists('commercial_clients', $column);
        }
    }

    private function addColumnIfMissing(string $table, string $column, string $definition): void
    {
        $check = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $this->pdo->quote($column));
        if ($check !== false && $check->fetch() === false) {
            $this->exec("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }

    private function dropColumnIfExists(string $table, string $column): void
    {
        $check = $this->pdo->query("SHOW COLUMNS FROM {$table} LIKE " . $this->pdo->quote($column));
        if ($check !== false && $check->fetch() !== false) {
            $this->exec("ALTER TABLE {$table} DROP COLUMN {$column}");
        }
    }
}
