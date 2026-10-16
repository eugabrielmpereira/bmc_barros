<?php
$client = $client ?? client_config();
$result = $result ?? null;
$error = $error ?? null;
$reference = $reference ?? '';
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php
      $pageTitle = 'Acompanhar pedido';
      $noindex = true;
      require __DIR__ . '/../partials/head.php';
    ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Pedido online</p>
        <h1>Acompanhar pedido</h1>
        <p>Informe a referência do pedido e os 4 últimos dígitos do telefone usado no checkout.</p>
      </div>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <?php if ($error): ?>
            <div class="commerce-alert commerce-alert--error"><?= e((string) $error) ?></div>
          <?php endif; ?>

          <?php if ($result === null): ?>
            <form method="POST" action="<?= BASE_URL ?>/acompanhar-pedido" class="commerce-form">
              <?= csrf_field() ?>
              <label>
                Referência do pedido
                <input type="text" name="public_reference" value="<?= e($reference) ?>" placeholder="Ex.: BMC-20260604-A1B2C3" required autocomplete="off" />
              </label>
              <label>
                4 últimos dígitos do telefone
                <input type="text" name="phone_last_digits" inputmode="numeric" maxlength="4" pattern="\d{4}" placeholder="0000" required autocomplete="off" />
              </label>
              <button type="submit" class="btn primary">Consultar pedido</button>
            </form>
          <?php else: ?>
            <div class="commerce-confirmation__steps">
              <h2>Pedido <?= e((string) ($result['public_reference'] ?? '')) ?></h2>
              <p><strong>Status:</strong> <?= e((string) ($result['status_label'] ?? '')) ?></p>
              <?php if (!empty($result['delivery_method_label'])): ?>
                <p><strong>Recebimento:</strong> <?= e((string) $result['delivery_method_label']) ?></p>
              <?php endif; ?>
              <?php if (!empty($result['created_at'])): ?>
                <p><strong>Recebido em:</strong> <?= e(function_exists('datetime_br') ? datetime_br((string) $result['created_at']) : (string) $result['created_at']) ?></p>
              <?php endif; ?>
              <?php if (!empty($result['shipping_city']) || !empty($result['shipping_state'])): ?>
                <p><strong>Entrega:</strong> <?= e(trim((string) ($result['shipping_city'] ?? '') . ' / ' . (string) ($result['shipping_state'] ?? ''), ' /')) ?></p>
              <?php elseif (($result['delivery_method'] ?? '') === 'pickup'): ?>
                <p><strong>Recebimento:</strong> Retirada na loja</p>
              <?php endif; ?>
            </div>

            <?php if (!empty($result['items'])): ?>
              <div class="commerce-confirmation__order-items">
                <h2>Itens</h2>
                <?php foreach ($result['items'] as $item): ?>
                  <div class="commerce-order-line">
                    <span><?= (int) ($item['quantity'] ?? 0) ?>× <?= e((string) ($item['product_name'] ?? 'Produto')) ?></span>
                    <span><?= formatarPreco((float) ($item['total_amount'] ?? 0)) ?></span>
                  </div>
                <?php endforeach; ?>
                <div class="commerce-order-total">
                  <span>Total indicativo</span>
                  <span><?= formatarPreco((float) ($result['total_amount'] ?? 0)) ?></span>
                </div>
              </div>
            <?php endif; ?>

            <p class="mt-3"><a href="<?= BASE_URL ?>/acompanhar-pedido">Consultar outro pedido</a> · <a href="<?= BASE_URL ?>">Voltar ao início</a></p>
          <?php endif; ?>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal($clientLegalName); ?>
    <?php site_nav_scripts(); ?>
    <?php commerce_assets(); ?>
  </body>
</html>
