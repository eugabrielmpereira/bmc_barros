<?php

declare(strict_types=1);

namespace Core\Cli;

use Core\Database\MasterDatabase;
use Core\Database\TenantMasterRepository;
use Core\Database\TenantSettingsRepository;
use PDO;
use RuntimeException;
use Shared\Support\Client;

/**
 * Cria usuário admin no banco do tenant.
 */
final class TenantAdminSeeder
{
    public static function create(string $email, string $plainPassword, ?PDO $pdo = null): void
    {
        $email = trim($email);
        if ($email === '' || $plainPassword === '') {
            throw new RuntimeException('E-mail e senha do admin são obrigatórios.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('E-mail admin inválido.');
        }

        if (strlen($plainPassword) < 8) {
            throw new RuntimeException('Senha admin deve ter no mínimo 8 caracteres.');
        }

        $pdo ??= \Core\Database::getInstance();

        $check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch() !== false) {
            throw new RuntimeException('Já existe usuário admin com este e-mail neste tenant.');
        }

        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        $pdo->prepare(
            'INSERT INTO usuarios (nome, email, senha, status, role) VALUES (?, ?, ?, ?, ?)'
        )->execute(['Administrador', $email, $hash, 'ativo', 'admin']);
    }
}
