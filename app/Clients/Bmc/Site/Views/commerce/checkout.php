<?php

$client = $client ?? client_config();

$cart = $cart ?? ['items' => [], 'item_count' => 0, 'subtotal' => 0.0];

$items = $cart['items'] ?? [];

$errors = $errors ?? [];

$fieldErrors = $field_errors ?? [];

$data = $data ?? [];

$clientName = (string) ($client['name'] ?? 'Cliente');

$clientLegalName = (string) ($client['legalName'] ?? $clientName);

?>

<!doctype html>

<html lang="pt-BR">

  <head>

    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Checkout | <?= e($clientName) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com" />

    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />

    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
    <?php commerce_page_styles(); ?>

  </head>

  <body>

    <?php site_topbar(['client' => $client]); ?>



    <main class="commerce-page">

      <div class="commerce-page-header">

        <p class="eyebrow">Pedido online</p>

        <h1>Finalizar pedido</h1>

        <p>Nossa equipe confirma valores, estoque e entrega em breve.</p>

      </div>



      <?php if ($items === []): ?>

        <div class="catalog-empty">

          <p>Seu carrinho está vazio.</p>

          <a class="btn primary" href="<?= BASE_URL ?>/produtos">Ver produtos</a>

        </div>

      <?php else: ?>

        <?php if ($errors !== []): ?>
          <div
            id="checkout-flash-errors"
            hidden
            data-messages="<?= e(json_encode(array_values($errors), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)) ?>"
          ></div>
        <?php endif; ?>



        <section class="commerce-layout">

          <form id="checkout-form" method="POST" action="<?= BASE_URL ?>/checkout" class="catalog-main">

            <?= csrf_field() ?>

            <div class="commerce-form">

              <h2>Dados do cliente</h2>

              <div class="commerce-form-row commerce-form-row-2">

                <label>Nome *

                  <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'name')) ?>" type="text" name="name" required

                         value="<?= e((string) ($data['name'] ?? '')) ?>" />

                  <?= commerce_checkout_field_error_html($fieldErrors, 'name') ?>

                </label>

                <label>Telefone / WhatsApp *

                  <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'phone')) ?>" type="text" name="phone"

                         value="<?= e((string) ($data['phone'] ?? '')) ?>" required />

                  <?= commerce_checkout_field_error_html($fieldErrors, 'phone') ?>

                </label>

              </div>

              <div class="commerce-form-row commerce-form-row-2">

                <label>E-mail *

                  <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'email')) ?>" type="email" name="email"

                         value="<?= e((string) ($data['email'] ?? '')) ?>" required />

                  <?= commerce_checkout_field_error_html($fieldErrors, 'email') ?>

                </label>

                <label>CPF/CNPJ *

                  <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'document')) ?>" type="text" name="document"

                         value="<?= e((string) ($data['document'] ?? '')) ?>" required />

                  <?= commerce_checkout_field_error_html($fieldErrors, 'document') ?>

                </label>

              </div>

              <?php require SHARED_PATH . '/View/site/checkout-delivery-section.php'; ?>

              <div class="commerce-form-row">

                <label>Observações

                  <textarea class="form-control" name="notes"

                            rows="3"><?= e((string) ($data['notes'] ?? '')) ?></textarea>

                </label>

              </div>

              <?php
              $privacyUrl = client_legal_page_url('privacy');
              $termsUrl = client_legal_page_url('terms');
              $termsAccepted = filter_var($data['accept_terms'] ?? false, FILTER_VALIDATE_BOOL)
                  || ($data['accept_terms'] ?? '') === '1';
              ?>
              <?php if ($privacyUrl !== null && $termsUrl !== null): ?>
                <div class="commerce-form-row commerce-legal-consent">
                  <label class="commerce-legal-consent__label">
                    <input
                      type="checkbox"
                      name="accept_terms"
                      value="1"
                      required
                      <?= $termsAccepted ? 'checked' : '' ?>
                    />
                    <span>
                      Li e aceito os
                      <a href="<?= e($termsUrl) ?>" target="_blank" rel="noopener noreferrer">Termos de Uso</a>
                      e a
                      <a href="<?= e($privacyUrl) ?>" target="_blank" rel="noopener noreferrer">Política de Privacidade</a>.
                    </span>
                  </label>
                  <?= commerce_checkout_field_error_html($fieldErrors, 'accept_terms') ?>
                </div>
              <?php endif; ?>

              <div class="commerce-form-actions">

                <a class="btn secondary" href="<?= BASE_URL ?>/carrinho">Voltar</a>

                <button class="btn primary" type="submit">Confirmar pedido</button>

              </div>

            </div>

          </form>



          <aside class="catalog-sidebar">

            <div class="commerce-summary">

              <h2>Resumo do pedido</h2>

              <div class="commerce-summary__lines">

                <?php foreach ($items as $item): ?>

                  <div class="commerce-summary__line">

                    <span><?= (int) $item['quantity'] ?>× <?= e((string) $item['name']) ?></span>

                  </div>

                <?php endforeach; ?>

              </div>

              <div class="commerce-summary__total">

                <span class="commerce-summary__total-label">Total indicativo</span>

                <span class="commerce-summary__total-value"><?= formatarPreco((float) $cart['subtotal']) ?></span>

              </div>

              <p class="commerce-summary__note">Valores e disponibilidade confirmados pela loja.</p>

            </div>

          </aside>

        </section>



        <div class="commerce-mobile-bar">

          <div class="commerce-mobile-bar__inner">

            <div class="commerce-mobile-bar__total">

              <span class="commerce-mobile-bar__label">Total indicativo</span>

              <span class="commerce-mobile-bar__value"><?= formatarPreco((float) $cart['subtotal']) ?></span>

            </div>

            <button class="btn primary" form="checkout-form" type="submit">Confirmar pedido</button>

          </div>

        </div>

      <?php endif; ?>

    </main>



    <?php commerce_footer_minimal($clientLegalName); ?>
    <?php site_nav_scripts(); ?>
    <?php commerce_assets(true); ?>
  </body>

</html>

