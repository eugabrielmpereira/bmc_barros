<?php
$client = $client ?? client_config();
$clientName = (string) ($client['name'] ?? 'Cliente');
$whatsappMessage = (string) ($whatsappMessage ?? ('Olá, ' . $clientName . '! Vim pelo site e gostaria de atendimento.'));
$brandHref = (string) ($brandHref ?? app_url());
?>
<header class="topbar">
  <a class="brand" href="<?= e($brandHref) ?>" aria-label="Ir para o início">
    <img src="<?= e(app_url((string) $client['assets']['logo'])) ?>" alt="Logomarca <?= e($clientName) ?>" />
    <span><strong><?= e($clientName) ?></strong><small><?= e((string) ($client['tagline'] ?? '')) ?></small></span>
  </a>
  <button class="menu-btn" type="button" aria-label="Abrir menu" aria-expanded="false">☰</button>
  <nav class="nav">
    <a href="<?= e(app_url()) ?>">Início</a>
    <a href="<?= e(app_url('/produtos')) ?>">Produtos</a>
    <?php commerce_cart_nav_link(); ?>
    <?php customer_nav_links(); ?>
    <a href="<?= e(app_url('/acompanhar-pedido')) ?>">Acompanhar pedido</a>
    <a href="<?= e(app_url('/#sobre')) ?>">Sobre</a>
    <a href="<?= e(app_url('/#localizacao')) ?>">Localização</a>
    <a href="tel:<?= e((string) $client['phoneHref']) ?>">Ligar</a>
  </nav>
  <a class="whatsapp-top" href="<?= e(client_whatsapp_url($whatsappMessage)) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
</header>
