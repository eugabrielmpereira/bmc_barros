<?php

function iniciarSessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function requireLogin(): void
{
    iniciarSessao();

    if (empty($_SESSION['user_id']) && empty($_SESSION['usuario_id'])) {
        header('Location: ' . BASE_URL . '/admin/login.php');
        exit;
    }
}

function exigirLogin(): void
{
    requireLogin();
}

function tentarLogin(string $email, string $senha): bool
{
    iniciarSessao();

    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT id, nome, email, senha FROM usuarios WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $usuario = $stmt->fetch();

    if (!$usuario || !password_verify($senha, $usuario['senha'])) {
        return false;
    }

    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['usuario_id'] = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['last_activity'] = time();

    session_regenerate_id(true);

    return true;
}

function loginUser(array $user): void
{
    iniciarSessao();
    session_regenerate_id(true);

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['usuario_id'] = $user['id'];
    $_SESSION['usuario_nome'] = $user['nome'] ?? ($user['name'] ?? 'Admin');
    $_SESSION['last_activity'] = time();
}

function logoutUser(): void
{
    iniciarSessao();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();
}

function fazerLogout(): void
{
    logoutUser();
    header('Location: ' . BASE_URL . '/admin/login.php');
    exit;
}