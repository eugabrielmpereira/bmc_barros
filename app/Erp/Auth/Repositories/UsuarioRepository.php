<?php

declare(strict_types=1);

namespace Erp\Auth\Repositories;

use Core\Database\Repository;
use PDO;

class UsuarioRepository extends Repository
{
    public function __construct(?PDO $pdo = null)
    {
        parent::__construct('usuarios', $pdo);
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = ? LIMIT 1'
        );
        $stmt->execute([trim($email)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findActiveByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM usuarios WHERE email = ? AND status = ? LIMIT 1'
        );
        $stmt->execute([trim($email), 'ativo']);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }
}
