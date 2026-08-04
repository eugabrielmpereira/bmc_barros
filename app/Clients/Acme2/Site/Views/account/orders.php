<?php
$client = $client ?? client_config();
$customer = $customer ?? [];
$orders = $orders ?? [];
$msg = $msg ?? null;
$tipo = $tipo ?? 'ok';
$clientName = (string) ($client['name'] ?? 'Cliente');
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Meus pedidos | <?= e($clientName) ?></title>
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
    <?php commerce_page_styles(); ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Minha conta</p>
        <h1>Meus pedidos</h1>
        <p>Olá, <?= e((string) ($customer['name'] ?? '')) ?>. Acompanhe suas compras online.</p>
      </div>

      <?php if ($msg): ?>
        <div class="commerce-alert commerce-alert--<?= e(commerce_alert_modifier((string) $tipo)) ?>"><?= e((string) $msg) ?></div>
      <?php endif; ?>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <nav class="account-subnav">
            <a href="<?= BASE_URL ?>/meus-pedidos" aria-current="page">Pedidos</a>
            <a href="<?= BASE_URL ?>/minha-conta">Dados pessoais</a>
            <a href="<?= BASE_URL ?>/minha-conta/enderecos">Endereços</a>
          </nav>

          <?php if ($orders === []): ?>
            <div class="catalog-empty">
              <p>Você ainda não fez pedidos online.</p>
              <a class="btn primary" href="<?= BASE_URL ?>/produtos">Ver produtos</a>
            </div>
          <?php else: ?>
            <div class="commerce-order-list">
              <?php foreach ($orders as $order): ?>
                <article class="commerce-order-card">
                  <div>
                    <strong><?= e((string) ($order['public_reference'] ?? '')) ?></strong>
                    <p><?= e((string) ($order['status_label'] ?? '')) ?></p>
                    <p class="commerce-order-card__meta">
                      <?php
                      $createdAt = (string) ($order['created_at'] ?? '');
                      if ($createdAt !== '') {
                          echo e(date('d/m/Y H:i', strtotime($createdAt)));
                      }
                      ?>
                      <?php if ((string) ($order['shipping_city'] ?? '') !== ''): ?>
                        · <?= e((string) $order['shipping_city']) ?>/<?= e((string) ($order['shipping_state'] ?? '')) ?>
                      <?php endif; ?>
                    </p>
                  </div>
                  <div class="commerce-order-card__actions">
                    <p class="commerce-order-card__total"><?= e(formatarPreco((float) ($order['total_amount'] ?? 0))) ?></p>
                    <a class="btn secondary" href="<?= BASE_URL ?>/meus-pedidos/<?= e(rawurlencode((string) ($order['public_reference'] ?? ''))) ?>">Detalhes</a>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
