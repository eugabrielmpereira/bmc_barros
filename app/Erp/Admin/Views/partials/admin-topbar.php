<?php
/** @var string $tituloPagina */
$adminNome = $_SESSION['admin_nome'] ?? 'Administrador';
?>
<header class="admin-topbar">
    <h1><?= htmlspecialchars($tituloPagina, ENT_QUOTES, 'UTF-8') ?></h1>
    <p class="admin-user"><?= htmlspecialchars((string) $adminNome, ENT_QUOTES, 'UTF-8') ?></p>
</header>
