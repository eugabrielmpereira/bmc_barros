<?php
// ============================================================
// admin/login.php — Página de login do painel administrativo
// ============================================================

// Autenticação já verificada no controller (se necessário)

$erro = $erro ?? '';
$client = $client ?? client_config();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title>Login — ERP Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;900&family=Oswald:wght@700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/erp/admin/css/login.css" />
</head>
<body>
    <div class="login-box">
        <div class="login-logo">
            <h1><?= htmlspecialchars((string) ($client['adminName'] ?? 'ERP Admin'), ENT_QUOTES, 'UTF-8') ?></h1>
            <p>Painel Administrativo</p>
            <span class="linha"></span>
        </div>

        <?php if ($erro): ?>
            <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_URL ?>/admin/login">
            <?= csrf_field() ?>

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email" placeholder="<?= htmlspecialchars((string) ($client['emailPlaceholder'] ?? 'admin@example.com'), ENT_QUOTES, 'UTF-8') ?>" required autocomplete="email" />

            <label for="senha">Senha</label>
            <input type="password" id="senha" name="senha" placeholder="••••••••" required autocomplete="current-password" />

            <button type="submit" class="btn-login">Entrar no Painel</button>
        </form>
    </div>
</body>
</html>
