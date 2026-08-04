<?php

declare(strict_types=1);

namespace Core\Database;

use PDO;

/**
 * Acesso base a dados via PDO (repositórios da aplicação estendem esta classe).
 */
abstract class Repository
{
    protected PDO $db;

    public function __construct(
        protected string $table,
        ?PDO $pdo = null
    ) {
        $this->db = $pdo ?? \Core\Database::getInstance();
    }

    public function getConnection(): PDO
    {
        return $this->db;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})"
        );

        return $stmt->execute(array_values($data));
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): bool
    {
        if ($data === []) {
            return false;
        }

        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $values = array_values($data);
        $values[] = $id;
        $stmt = $this->db->prepare("UPDATE {$this->table} SET {$set} WHERE id = ?");

        return $stmt->execute($values);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");

        return $stmt->execute([$id]);
    }
}
