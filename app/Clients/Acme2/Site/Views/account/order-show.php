<?php
$client = $client ?? client_config();
$order = $order ?? [];
$clientName = (string) ($client['name'] ?? 'Cliente');
$items = $order['items'] ?? [];
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pedido <?= e((string) ($order['public_reference'] ?? '')) ?> | <?= e($clientName) ?></title>
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
    <?php commerce_page_styles(); ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Meus pedidos</p>
        <h1><?= e((string) ($order['public_reference'] ?? '')) ?></h1>
        <p><?= e((string) ($order['status_label'] ?? '')) ?></p>
      </div>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <nav class="account-subnav">
            <a href="<?= BASE_URL ?>/meus-pedidos">Voltar aos pedidos</a>
          </nav>

          <div class="commerce-summary">
            <p><strong>Total:</strong> <?= e(formatarPreco((float) ($order['total_amount'] ?? 0))) ?></p>
            <?php if ((string) ($order['shipping_street'] ?? '') !== ''): ?>
              <p><strong>Entrega:</strong>
                <?= e((string) $order['shipping_street']) ?>, <?= e((string) ($order['shipping_number'] ?? '')) ?>
                — <?= e((string) ($order['shipping_neighborhood'] ?? '')) ?>,
                <?= e((string) ($order['shipping_city'] ?? '')) ?>/<?= e((string) ($order['shipping_state'] ?? '')) ?>
              </p>
            <?php elseif (($order['delivery_method'] ?? '') === 'pickup'): ?>
              <p><strong>Recebimento:</strong> Retirada na loja</p>
            <?php endif; ?>
          </div>

          <h2>Itens</h2>
          <ul class="commerce-order-items">
            <?php foreach ($items as $item): ?>
              <li>
                <?= (int) ($item['quantity'] ?? 0) ?>× <?= e((string) ($item['product_name'] ?? 'Produto')) ?>
                — <?= e(formatarPreco((float) ($item['total_amount'] ?? 0))) ?>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
