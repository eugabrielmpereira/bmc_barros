<?php
$client = $client ?? client_config();
$order = $order ?? [];
$items = $items ?? [];
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
$orderId = (int) ($order['id'] ?? 0);
$orderReference = trim((string) ($order['public_reference'] ?? ''));
$orderDisplayRef = $orderReference !== '' ? $orderReference : ('#' . $orderId);
$whatsappMessage = 'Olá, ' . $clientName . '! Acabei de enviar o pedido '
    . $orderDisplayRef . ' pelo site e aguardo retorno.';
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Pedido recebido | <?= e($clientName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
    <?php commerce_page_styles(); ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client, 'whatsappMessage' => $whatsappMessage]); ?>

    <main class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Pedido online</p>
        <h1>Pedido recebido ✔</h1>
        <p>Seu pedido <strong><?= e($orderDisplayRef) ?></strong> foi enviado com sucesso.</p>
      </div>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <div class="commerce-confirmation__steps">
            <h2>Próximos passos</h2>
            <ol>
              <li>Nossa equipe confirma preços, estoque e condições de entrega ou retirada.</li>
              <li>Entraremos em contato pelo telefone ou WhatsApp informados.</li>
              <li>Após sua aprovação, combinamos retirada na loja ou entrega.</li>
            </ol>
            <p><strong>Status:</strong> Aguardando confirmação da loja</p>
            <p><a href="<?= BASE_URL ?>/acompanhar-pedido<?= $orderReference !== '' ? '?ref=' . urlencode($orderReference) : '' ?>">Acompanhar este pedido</a></p>
            <?php if (!empty($order['client_name'])): ?>
              <p><strong>Cliente:</strong> <?= e((string) $order['client_name']) ?></p>
            <?php endif; ?>
          </div>

          <?php if ($items !== []): ?>
            <div class="commerce-confirmation__order-items">
              <h2>Resumo do pedido</h2>
              <?php foreach ($items as $item): ?>
                <div class="commerce-order-line">
                  <span><?= (int) ($item['quantity'] ?? 0) ?>× <?= e((string) ($item['product_name'] ?? 'Produto')) ?></span>
                  <span><?= formatarPreco((float) ($item['total_amount'] ?? 0)) ?></span>
                </div>
              <?php endforeach; ?>
              <div class="commerce-order-total">
                <span>Total indicativo</span>
                <span><?= formatarPreco((float) ($order['total_amount'] ?? 0)) ?></span>
              </div>
              <p class="commerce-summary__note">Valores e disponibilidade confirmados pela loja.</p>
            </div>
          <?php endif; ?>

          <?php require SHARED_PATH . '/View/site/pix-payment-block.php'; ?>

          <div class="commerce-summary__actions">
            <a class="btn primary" href="<?= e(client_whatsapp_url($whatsappMessage)) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
            <a class="btn secondary" href="<?= BASE_URL ?>">Voltar ao início</a>
            <a class="btn secondary" href="<?= BASE_URL ?>/produtos">Continuar comprando</a>
          </div>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal($clientLegalName); ?>
    <?php site_nav_scripts(); ?>
    <?php commerce_assets(); ?>
  </body>
</html>
