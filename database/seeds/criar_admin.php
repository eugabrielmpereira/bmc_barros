#!/usr/bin/env php
<?php

/**
 * Cria o primeiro usuário administrador (senha com password_hash).
 *
 * Uso (CLI):
 *   php database/seeds/criar_admin.php "admin@exemplo.com" "SenhaForte123"
 */

declare(strict_types=1);

use Core\Database;

if (php_sapi_name() !== 'cli') {
    fwrite(STDERR, "Execute apenas via linha de comando.\n");
    exit(1);
}

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/config/app.php';

$email = isset($argv[1]) ? trim((string) $argv[1]) : '';
$senhaPlana = isset($argv[2]) ? (string) $argv[2] : '';

if ($email === '' || $senhaPlana === '') {
    fwrite(STDERR, "Uso: php database/seeds/criar_admin.php <email> <senha>\n");
    exit(1);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "E-mail inválido.\n");
    exit(1);
}

if (strlen($senhaPlana) < 8) {
    fwrite(STDERR, "Senha deve ter no mínimo 8 caracteres.\n");
    exit(1);
}

$pdo = Database::getInstance();

// Tabela antiga sem coluna status: alinha schema antes do INSERT (evita depender só da migration).
$statusCol = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'status'");
if ($statusCol !== false && $statusCol->fetch() === false) {
    $pdo->exec(
        "ALTER TABLE usuarios ADD COLUMN status VARCHAR(20) NOT NULL DEFAULT 'ativo'"
    );
    fwrite(STDOUT, "Coluna 'status' adicionada em usuarios.\n");
}

$roleCol = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'role'");
if ($roleCol !== false && $roleCol->fetch() === false) {
    $pdo->exec(
        "ALTER TABLE usuarios ADD COLUMN role VARCHAR(40) NOT NULL DEFAULT 'admin'"
    );
    fwrite(STDOUT, "Coluna 'role' adicionada em usuarios.\n");
}

$check = $pdo->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
$check->execute([$email]);
if ($check->fetch() !== false) {
    fwrite(STDERR, "Já existe usuário com este e-mail. Use outro e-mail ou altere pelo banco.\n");
    exit(1);
}

$hash = password_hash($senhaPlana, PASSWORD_DEFAULT);
$stmt = $pdo->prepare(
    'INSERT INTO usuarios (nome, email, senha, status, role) VALUES (?, ?, ?, ?, ?)'
);
$stmt->execute(['Administrador', $email, $hash, 'ativo', 'admin']);

fwrite(STDOUT, "Usuário admin criado: {$email}\n");
exit(0);
