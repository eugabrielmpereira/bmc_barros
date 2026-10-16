<?php
use Erp\Finance\Services\PaymentChargeService;
use Erp\Finance\Support\PaymentMethod;
use Erp\Fulfillment\Support\DeliveryMethod;

$client = $client ?? client_config();
$order = $order ?? [];
$items = $items ?? [];
$charge = $pix_charge ?? null;
$paymentMethod = $payment_method ?? PaymentMethod::parse((string) ($order['payment_method'] ?? PaymentMethod::PIX));
$storeName = (string) ($client['name'] ?? 'Loja');
$orderId = (int) ($order['id'] ?? 0);
$orderReference = trim((string) ($order['public_reference'] ?? ''));
$orderDisplayRef = $orderReference !== '' ? $orderReference : ('#' . $orderId);
$isPickup = DeliveryMethod::parse((string) ($order['delivery_method'] ?? '')) === DeliveryMethod::PICKUP;
$shippingFee = (float) ($order['shipping_fee'] ?? 0);
$installments = max(1, (int) ($order['installments'] ?? 1));
$itemsTotal = array_reduce($items, static fn (float $sum, array $item): float => $sum + (float) ($item['total_amount'] ?? 0), 0.0);
$isPaid = is_array($charge) && PaymentChargeService::isPaidStatus((string) ($charge['status'] ?? ''));
$whatsMessage = 'Olá, ' . $storeName . '! Acabei de fazer o pedido ' . $orderDisplayRef . ' pelo site.';
$trackUrl = app_url('/acompanhar-pedido' . ($orderReference !== '' ? '?ref=' . urlencode($orderReference) : ''));

$pageTitle = 'Pedido recebido';
$noindex = true;
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="page">
      <div class="wrap receipt">
        <section class="surface receipt__head">
          <div class="receipt__check"><?= bmc_icon('check') ?></div>
          <h1 class="display">Pedido recebido</h1>
          <p>Guarde o número do seu pedido:</p>
          <span class="receipt__ref"><?= e($orderDisplayRef) ?></span>
        </section>

        <?php if ($paymentMethod === PaymentMethod::CREDIT_CARD): ?>
          <?php $invoiceUrl = is_array($charge) ? trim((string) ($charge['invoice_url'] ?? '')) : ''; ?>
          <section class="commerce-pix-payment card-payment" aria-labelledby="pagamento-titulo">
            <h2 id="pagamento-titulo">Pagamento com cartão<?= $installments > 1 ? ' em ' . $installments . 'x' : '' ?></h2>
            <?php if ($isPaid): ?>
              <p class="commerce-pix-payment__status--paid">Pagamento aprovado. A loja já está separando seu pedido.</p>
            <?php elseif ($invoiceUrl !== ''): ?>
              <p>Clique no botão para informar o cartão na página segura do Asaas. O pedido só é separado depois da aprovação.</p>
              <a class="btn btn--primary btn--lg" href="<?= e($invoiceUrl) ?>" target="_blank" rel="noopener noreferrer"><?= bmc_icon('card') ?> Pagar com cartão</a>
            <?php else: ?>
              <p>Não conseguimos abrir o pagamento agora. A loja vai enviar o link pelo WhatsApp, ou você pode pedir pelo botão abaixo.</p>
            <?php endif; ?>
          </section>
        <?php else: ?>
          <?php
            $pix_charge = $charge;
            require SHARED_PATH . '/View/site/pix-payment-block.php';
          ?>
          <?php if (!is_array($charge)): ?>
            <section class="surface receipt__block">
              <h2>Pagamento</h2>
              <p>A loja confirma o pedido e envia a chave Pix pelo WhatsApp.</p>
            </section>
          <?php endif; ?>
        <?php endif; ?>

        <section class="surface receipt__block" aria-labelledby="proximos-titulo">
          <h2 id="proximos-titulo">O que acontece agora</h2>
          <ol class="receipt__steps">
            <li>A loja confere o estoque e confirma seu pedido.</li>
            <li><?= $isPickup ? 'Avisamos pelo WhatsApp quando estiver pronto para retirar.' : 'Combinamos o horário da entrega pelo WhatsApp.' ?></li>
            <li>Você acompanha o status a qualquer momento pelo número do pedido.</li>
          </ol>
        </section>

        <?php if ($items !== []): ?>
          <section class="surface receipt__block" aria-labelledby="itens-titulo">
            <h2 id="itens-titulo">Resumo</h2>
            <?php foreach ($items as $item): ?>
              <div class="commerce-order-line">
                <span><?= (int) ($item['quantity'] ?? 0) ?>× <?= e((string) ($item['product_name'] ?? 'Produto')) ?></span>
                <span><?= formatarPreco((float) ($item['total_amount'] ?? 0)) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="commerce-order-line">
              <span><?= $isPickup ? 'Retirada na loja' : 'Frete' . (!empty($order['delivery_zone_name']) ? ' (' . e((string) $order['delivery_zone_name']) . ')' : '') ?></span>
              <span><?= $isPickup || ($shippingFee <= 0 && !empty($order['delivery_zone_name'])) ? 'Grátis' : ($shippingFee > 0 ? formatarPreco($shippingFee) : 'A combinar') ?></span>
            </div>
            <div class="commerce-order-total">
              <span>Total</span>
              <span><?= formatarPreco((float) ($order['total_amount'] ?? ($itemsTotal + $shippingFee))) ?></span>
            </div>
          </section>
        <?php endif; ?>

        <div class="receipt__actions">
          <a class="btn btn--whats" href="<?= e(client_whatsapp_url($whatsMessage)) ?>" target="_blank" rel="noreferrer"><?= bmc_icon('whatsapp') ?> Falar com a loja</a>
          <a class="btn btn--ghost" href="<?= e($trackUrl) ?>">Acompanhar pedido</a>
          <a class="btn btn--ghost" href="<?= e(app_url('/produtos')) ?>">Continuar comprando</a>
        </div>
      </div>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
