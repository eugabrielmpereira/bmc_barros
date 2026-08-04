<?php

declare(strict_types=1);

namespace Erp\Stock\Repositories;

use Core\Database\Repository;
use PDO;

/**
 * Acesso à tabela `stock_movements` (histórico de movimentações de estoque).
 * Regras de negócio ficam no StockService (Sprint 4 — próximas fases).
 */
class StockMovementRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('stock_movements', $pdo);
    }

    /**
     * Insere um registro de movimentação.
     * `created_at` é preenchido pelo banco (DEFAULT CURRENT_TIMESTAMP).
     *
     * @param 'entrada'|'saida'|'ajuste' $type
     */
    public function createMovement(
        int $produtoId,
        string $type,
        int $quantity,
        int $previousStock,
        int $newStock,
        ?string $reason,
        ?int $userId
    ): void {
        $sql = "INSERT INTO {$this->table}
                    (produto_id, type, quantity, previous_stock, new_stock, reason, user_id)
                VALUES (?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $produtoId,
            $type,
            $quantity,
            $previousStock,
            $newStock,
            $reason,
            $userId,
        ]);
    }

    /**
     * Últimas movimentações de um produto (mais recentes primeiro).
     *
     * @return list<array<string, mixed>>
     */
    public function getLastMovementsByProduct(int $produtoId, int $limit = 10): array
    {
        $limit = max(1, min(100, $limit));

        $sql = "SELECT type, quantity, previous_stock, new_stock, reason, created_at
                FROM {$this->table}
                WHERE produto_id = ?
                ORDER BY created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $produtoId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Movimentações recentes de todos os produtos (uso futuro no dashboard).
     *
     * @return list<array<string, mixed>>
     */
    public function getRecentMovements(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));

        $sql = "SELECT p.nome AS produto_nome, sm.type, sm.quantity, sm.created_at
                FROM {$this->table} sm
                INNER JOIN produtos p ON p.id = sm.produto_id
                ORDER BY sm.created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Lista as movimentações mais recentes para o histórico admin.
     *
     * @return list<array{
     *     movimento_id: int|string,
     *     produto_id: int|string,
     *     produto_nome: string,
     *     tipo: string,
     *     quantidade: int|string,
     *     motivo: string|null,
     *     user_id: int|string|null,
     *     created_at: string
     * }>
     */
    public function listLatestMovements(int $limit = 50): array
    {
        $limit = max(1, min(100, $limit));

        $sql = "SELECT
                    sm.id AS movimento_id,
                    sm.produto_id,
                    p.nome AS produto_nome,
                    sm.type AS tipo,
                    sm.quantity AS quantidade,
                    sm.reason AS motivo,
                    sm.user_id,
                    sm.created_at
                FROM {$this->table} sm
                INNER JOIN produtos p ON p.id = sm.produto_id
                ORDER BY sm.created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @return list<array{
     *     movimento_id: int|string,
     *     produto_id: int|string,
     *     produto_nome: string,
     *     tipo: string,
     *     quantidade: int|string,
     *     motivo: string|null,
     *     user_id: int|string|null,
     *     created_at: string
     * }>
     */
    public function listMovementsByPeriod(string $startDateTime, string $endDateTime, int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));

        $sql = "SELECT
                    sm.id AS movimento_id,
                    sm.produto_id,
                    p.nome AS produto_nome,
                    sm.type AS tipo,
                    sm.quantity AS quantidade,
                    sm.reason AS motivo,
                    sm.user_id,
                    sm.created_at
                FROM {$this->table} sm
                INNER JOIN produtos p ON p.id = sm.produto_id
                WHERE sm.created_at BETWEEN ? AND ?
                ORDER BY sm.created_at DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $startDateTime);
        $stmt->bindValue(2, $endDateTime);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
