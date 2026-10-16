<?php
use Erp\Finance\Support\PaymentMethod;
use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Shipping\Services\ShippingQuoteService;

$client = $client ?? client_config();
$cart = $cart ?? ['items' => [], 'item_count' => 0, 'subtotal' => 0.0];
$items = $cart['items'] ?? [];
$errors = $errors ?? [];
$fieldErrors = $field_errors ?? [];
$data = $data ?? [];
$quote = $shipping_quote ?? null;
$orderTotal = (float) ($order_total ?? ($cart['subtotal'] ?? 0));
$cardEnabled = (bool) ($card_enabled ?? false);
$installmentOptions = $installment_options ?? [];
$subtotal = (float) ($cart['subtotal'] ?? 0);
$deliveryMethod = DeliveryMethod::parse((string) ($data['delivery_method'] ?? DeliveryMethod::DELIVERY));
$paymentMethod = PaymentMethod::parse((string) ($data['payment_method'] ?? PaymentMethod::PIX));
if (!$cardEnabled) {
    $paymentMethod = PaymentMethod::PIX;
}
$selectedInstallments = max(1, (int) ($data['installments'] ?? 1));
$privacyUrl = client_legal_page_url('privacy');
$termsUrl = client_legal_page_url('terms');
$termsAccepted = filter_var($data['accept_terms'] ?? false, FILTER_VALIDATE_BOOL) || ($data['accept_terms'] ?? '') === '1';

// Estado inicial do frete (o JS recalcula conforme o endereço muda)
$hasAddress = trim((string) ($data['shipping_city'] ?? '')) !== '';
$shippingLabel = 'Informe o endereço';
$shippingState = 'pending';
$shippingMessage = 'Preencha CEP, bairro e cidade para calcular.';
if (is_array($quote)) {
    if ($quote['mode'] === ShippingQuoteService::MODE_PICKUP) {
        $shippingLabel = 'Grátis';
        $shippingState = 'ok';
        $shippingMessage = 'Retirada na loja sem custo.';
    } elseif ($quote['mode'] === ShippingQuoteService::MODE_TO_ARRANGE) {
        $shippingLabel = 'A combinar';
        $shippingState = 'ok';
        $shippingMessage = (string) $quote['message'];
    } elseif ($hasAddress) {
        $shippingState = $quote['available'] ? 'ok' : 'error';
        $shippingLabel = $quote['available'] ? ((float) $quote['fee'] > 0 ? formatarPreco((float) $quote['fee']) : 'Grátis') : '—';
        $shippingMessage = (string) $quote['message'];
    }
}

$pageTitle = 'Fechar pedido';
$noindex = true;
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
  </head>
  <body class="<?= $items !== [] ? 'has-mobile-bar' : '' ?>">
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="page">
      <div class="wrap">
        <nav class="breadcrumb" aria-label="Você está em">
          <a href="<?= e(app_url('/carrinho')) ?>">Carrinho</a>
          <?= bmc_icon('chevron') ?>
          <span>Fechar pedido</span>
        </nav>
        <div class="page-head">
          <h1 class="display">Fechar pedido</h1>
        </div>

        <?php if ($items === []): ?>
          <div class="surface catalog-empty">
            <h2>Seu carrinho está vazio</h2>
            <p>Adicione produtos antes de fechar o pedido.</p>
            <div class="hero__actions"><a class="btn btn--primary" href="<?= e(app_url('/produtos')) ?>">Ver produtos</a></div>
          </div>
        <?php else: ?>
          <?php if ($errors !== []): ?>
            <div class="commerce-alert commerce-alert--error" role="alert">
              <p><strong>Revise o pedido:</strong></p>
              <ul>
                <?php foreach ($errors as $error): ?><li><?= e((string) $error) ?></li><?php endforeach; ?>
              </ul>
            </div>
          <?php endif; ?>

          <div class="checkout-layout">
            <form id="checkout-form" method="POST" action="<?= e(app_url('/checkout')) ?>"
                  data-shipping-quote-url="<?= e(app_url('/frete/cotacao')) ?>">
              <?= csrf_field() ?>

              <section class="surface checkout-step commerce-form" aria-labelledby="passo-dados">
                <h2 class="checkout-step__title" id="passo-dados"><span class="checkout-step__num">1</span> Seus dados</h2>
                <div class="commerce-form-row commerce-form-row-2">
                  <label>Nome completo
                    <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'name')) ?>" type="text" name="name" required autocomplete="name" value="<?= e((string) ($data['name'] ?? '')) ?>" />
                    <?= commerce_checkout_field_error_html($fieldErrors, 'name') ?>
                  </label>
                  <label>Celular / WhatsApp
                    <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'phone')) ?>" type="tel" name="phone" required autocomplete="tel" inputmode="tel" placeholder="(16) 99999-9999" value="<?= e((string) ($data['phone'] ?? '')) ?>" />
                    <?= commerce_checkout_field_error_html($fieldErrors, 'phone') ?>
                  </label>
                </div>
                <div class="commerce-form-row commerce-form-row-2">
                  <label>E-mail
                    <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'email')) ?>" type="email" name="email" required autocomplete="email" value="<?= e((string) ($data['email'] ?? '')) ?>" />
                    <?= commerce_checkout_field_error_html($fieldErrors, 'email') ?>
                  </label>
                  <label>CPF ou CNPJ
                    <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'document')) ?>" type="text" name="document" required inputmode="numeric" value="<?= e((string) ($data['document'] ?? '')) ?>" />
                    <?= commerce_checkout_field_error_html($fieldErrors, 'document') ?>
                  </label>
                </div>
                <?php if (!($logged_in ?? false)): ?>
                  <p class="pay-note">Já comprou com a gente? <a href="<?= e(app_url('/entrar?redirect=/checkout')) ?>">Entre na sua conta</a> para preencher mais rápido.</p>
                <?php endif; ?>
              </section>

              <section class="surface checkout-step commerce-form" aria-labelledby="passo-entrega">
                <h2 class="checkout-step__title" id="passo-entrega"><span class="checkout-step__num">2</span> Entrega</h2>
                <fieldset class="choice-group" data-delivery-method-fieldset>
                  <legend class="visually-hidden">Como você quer receber</legend>
                  <label class="choice">
                    <input type="radio" name="delivery_method" value="<?= e(DeliveryMethod::DELIVERY) ?>" <?= $deliveryMethod === DeliveryMethod::DELIVERY ? 'checked' : '' ?> data-delivery-method-input />
                    <?= bmc_icon('truck') ?>
                    <span><strong>Receber em casa ou na obra</strong><small>Caminhão da loja, frete pelo bairro</small></span>
                  </label>
                  <label class="choice">
                    <input type="radio" name="delivery_method" value="<?= e(DeliveryMethod::PICKUP) ?>" <?= $deliveryMethod === DeliveryMethod::PICKUP ? 'checked' : '' ?> data-delivery-method-input />
                    <?= bmc_icon('store') ?>
                    <span><strong>Retirar na loja</strong><small>Sem custo, avisamos quando estiver pronto</small></span>
                  </label>
                </fieldset>

                <div data-checkout-address-section class="checkout-address">
                  <?php require SHARED_PATH . '/View/site/checkout-address-fields.php'; ?>
                </div>

                <div class="shipping-result shipping-result--<?= e($shippingState) ?>" data-shipping-result aria-live="polite">
                  <?= bmc_icon($deliveryMethod === DeliveryMethod::PICKUP ? 'store' : 'truck') ?>
                  <div>
                    <strong data-shipping-title><?= $deliveryMethod === DeliveryMethod::PICKUP ? 'Retirada na loja' : 'Frete: ' . e($shippingLabel) ?></strong>
                    <span data-shipping-message><?= e($shippingMessage) ?><?= is_array($quote) && !empty($quote['lead_time']) && $quote['available'] ? ' ' . e((string) $quote['lead_time']) . '.' : '' ?></span>
                  </div>
                </div>
                <?= commerce_checkout_field_error_html($fieldErrors, 'shipping') ?>
              </section>

              <section class="surface checkout-step commerce-form" aria-labelledby="passo-pagamento">
                <h2 class="checkout-step__title" id="passo-pagamento"><span class="checkout-step__num">3</span> Pagamento</h2>
                <fieldset class="choice-group">
                  <legend class="visually-hidden">Forma de pagamento</legend>
                  <label class="choice">
                    <input type="radio" name="payment_method" value="<?= e(PaymentMethod::PIX) ?>" <?= $paymentMethod === PaymentMethod::PIX ? 'checked' : '' ?> data-payment-method-input />
                    <?= bmc_icon('pix') ?>
                    <span><strong>Pix</strong><small>QR Code na próxima tela, aprovação na hora</small></span>
                  </label>
                  <?php if ($cardEnabled): ?>
                    <label class="choice">
                      <input type="radio" name="payment_method" value="<?= e(PaymentMethod::CREDIT_CARD) ?>" <?= $paymentMethod === PaymentMethod::CREDIT_CARD ? 'checked' : '' ?> data-payment-method-input />
                      <?= bmc_icon('card') ?>
                      <span><strong>Cartão de crédito</strong><small>Em até <?= PaymentMethod::maxInstallments() ?>x sem juros, em página segura</small></span>
                    </label>
                  <?php endif; ?>
                </fieldset>

                <?php if ($cardEnabled): ?>
                  <label class="field installments-field" data-installments-field <?= $paymentMethod === PaymentMethod::CREDIT_CARD ? '' : 'hidden' ?>>
                    Parcelas
                    <select class="form-control" name="installments" data-installments-select>
                      <?php foreach ($installmentOptions as $option): ?>
                        <option value="<?= (int) $option['count'] ?>" <?= $selectedInstallments === (int) $option['count'] ? 'selected' : '' ?>><?= e($option['label']) ?></option>
                      <?php endforeach; ?>
                    </select>
                  </label>
                  <p class="pay-note" data-card-note <?= $paymentMethod === PaymentMethod::CREDIT_CARD ? '' : 'hidden' ?>>Depois de confirmar, você informa o cartão na página segura do Asaas. A loja não vê nem guarda os dados do seu cartão.</p>
                <?php endif; ?>
              </section>

              <section class="surface checkout-step commerce-form" aria-labelledby="passo-revisao">
                <h2 class="checkout-step__title" id="passo-revisao"><span class="checkout-step__num">4</span> Confirmar</h2>
                <label>Observações para a entrega (opcional)
                  <textarea class="form-control" name="notes" rows="3" placeholder="Ex.: entregar pela manhã, descarregar no fundo do terreno"><?= e((string) ($data['notes'] ?? '')) ?></textarea>
                </label>
                <?php if ($privacyUrl !== null && $termsUrl !== null): ?>
                  <div class="commerce-legal-consent">
                    <label class="commerce-legal-consent__label">
                      <input type="checkbox" name="accept_terms" value="1" required <?= $termsAccepted ? 'checked' : '' ?> />
                      <span>Li e aceito os <a href="<?= e($termsUrl) ?>" target="_blank" rel="noopener noreferrer">termos de uso</a> e a <a href="<?= e($privacyUrl) ?>" target="_blank" rel="noopener noreferrer">política de privacidade</a>.</span>
                    </label>
                    <?= commerce_checkout_field_error_html($fieldErrors, 'accept_terms') ?>
                  </div>
                <?php endif; ?>
                <button class="btn btn--primary btn--lg btn--block" type="submit" data-checkout-submit>Confirmar pedido</button>
              </section>
            </form>

            <aside class="surface summary" aria-labelledby="resumo-titulo">
              <h2 id="resumo-titulo">Seu pedido</h2>
              <ul class="summary__items">
                <?php foreach ($items as $item): ?>
                  <li><span><?= (int) $item['quantity'] ?>× <?= e((string) $item['name']) ?></span><span><?= formatarPreco((float) $item['total']) ?></span></li>
                <?php endforeach; ?>
              </ul>
              <div class="summary__row"><span>Subtotal</span><span><?= formatarPreco($subtotal) ?></span></div>
              <div class="summary__row"><span>Frete</span><span data-summary-shipping><?= e($shippingLabel) ?></span></div>
              <div class="summary__row summary__row--total"><span>Total</span><span data-summary-total><?= formatarPreco($orderTotal) ?></span></div>
              <p class="summary__free-hint" data-free-hint <?= is_array($quote) && !empty($quote['missing_for_free_shipping']) ? '' : 'hidden' ?>>
                <?php if (is_array($quote) && !empty($quote['missing_for_free_shipping'])): ?>
                  Faltam <?= e(formatarPreco((float) $quote['missing_for_free_shipping'])) ?> para frete grátis.
                <?php endif; ?>
              </p>
              <p class="summary__note">A loja confere estoque e separa o pedido. Você acompanha tudo pelo WhatsApp.</p>
            </aside>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <?php if ($items !== []): ?>
      <div class="mobile-bar">
        <div class="mobile-bar__total"><span>Total</span><strong data-summary-total-mobile><?= formatarPreco($orderTotal) ?></strong></div>
        <button class="btn btn--primary" form="checkout-form" type="submit">Confirmar pedido</button>
      </div>
    <?php endif; ?>

    <?php commerce_footer_minimal(); ?>
    <?php commerce_assets(true); ?>
    <script src="<?= client_asset('js/checkout.js') ?>" defer></script>
    <?php site_nav_scripts(); ?>
  </body>
</html>
