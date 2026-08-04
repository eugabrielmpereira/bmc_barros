<?php
/** @var string $content */
/** @var string $tituloPagina */
/** @var string $paginaAtiva */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <?php partial('head-admin', ['tituloPagina' => $tituloPagina]); ?>
</head>
<body class="admin-body<?= ($_SESSION['admin_role'] ?? '') === 'admin' ? ' admin-role-admin' : '' ?>">
    <div class="admin-shell">
        <?php partial('admin-sidebar', ['paginaAtiva' => $paginaAtiva]); ?>
        <div class="admin-main">
            <?php partial('admin-topbar', ['tituloPagina' => $tituloPagina]); ?>
            <main class="admin-container">
                <?= $content ?>
            </main>
        </div>
    </div>
</body>
</html>
