<?php

if (customer_is_logged_in()):
    $displayName = customer_display_name();
?>
    <a href="<?= e(app_url('/meus-pedidos')) ?>">Meus pedidos</a>
    <a href="<?= e(app_url('/minha-conta')) ?>"><?= $displayName !== '' ? e($displayName) : 'Minha conta' ?></a>
    <form method="POST" action="<?= e(app_url('/sair')) ?>" class="customer-logout-form">
        <?= csrf_field() ?>
        <button type="submit" class="nav-link-button">Sair</button>
    </form>
<?php else: ?>
    <a href="<?= e(app_url('/entrar')) ?>">Entrar</a>
    <a href="<?= e(app_url('/cadastro')) ?>">Cadastrar</a>
<?php endif; ?>
