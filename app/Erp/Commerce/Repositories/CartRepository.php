<?php

declare(strict_types=1);

namespace Erp\Commerce\Repositories;

use Core\Database\Repository;
use PDO;

final class CartRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('carts', $pdo);
    }

    public function findOpenByUserId(int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE user_id = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findOpenBySessionToken(string $sessionToken): ?array
    {
        if ($sessionToken === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE session_token = ? AND status = 'open' LIMIT 1");
        $stmt->execute([$sessionToken]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findBySessionToken(string $sessionToken): ?array
    {
        if ($sessionToken === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE session_token = ? LIMIT 1");
        $stmt->execute([$sessionToken]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function createCart(?int $userId, string $sessionToken): int
    {
        $this->create([
            'user_id' => $userId,
            'session_token' => $sessionToken !== '' ? $sessionToken : null,
            'status' => 'open',
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function associateUser(int $cartId, int $userId): bool
    {
        return $this->update($cartId, ['user_id' => $userId]);
    }

    public function markOrdered(int $cartId): bool
    {
        // Libera o token para não colidir com novos carrinhos no mesmo browser.
        return $this->update($cartId, [
            'status' => 'ordered',
            'session_token' => null,
        ]);
    }
}
