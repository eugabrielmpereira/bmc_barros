<?php

$client = $client ?? client_config();

$cart = $cart ?? ['items' => [], 'item_count' => 0, 'subtotal' => 0.0];

$items = $cart['items'] ?? [];

$clientName = (string) ($client['name'] ?? 'Cliente');

$clientLegalName = (string) ($client['legalName'] ?? $clientName);

$msg = $msg ?? null;

$tipo = $tipo ?? 'ok';

?>

<!doctype html>

<html lang="pt-BR">

  <head>

    <meta charset="UTF-8" />

    <meta name="viewport" content="width=device-width, initial-scale=1.0" />

    <title>Carrinho | <?= e($clientName) ?></title>

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

        <h1>Carrinho</h1>

        <p>Revise os itens antes de finalizar seu pedido.</p>

      </div>



      <?php if ($msg): ?>
        <div class="commerce-alert commerce-alert--<?= commerce_alert_modifier((string) $tipo) ?>">
          <p><?= e((string) $msg) ?></p>
        </div>
      <?php endif; ?>



      <?php if ($items === []): ?>

        <div class="catalog-empty">

          <p>Seu carrinho está vazio.</p>

          <a class="btn primary" href="<?= BASE_URL ?>/produtos">Ver produtos</a>

        </div>

      <?php else: ?>

        <form method="POST" action="<?= BASE_URL ?>/carrinho/atualizar" class="commerce-layout" data-commerce-cart-update>

          <?= csrf_field() ?>

          <div class="commerce-layout__main">

            <div class="commerce-cart-lines">

              <?php foreach ($items as $item): ?>

                <div class="commerce-cart-line">

                  <?php if (!empty($item['image'])): ?>

                    <img class="commerce-cart-line__thumb"

                         src="<?= e(client_product_image_url((string) $item['image'])) ?>"

                         alt="<?= e((string) $item['name']) ?>" />

                  <?php else: ?>

                    <div class="commerce-cart-line__thumb-empty" aria-hidden="true">
                      <span class="commerce-cart-line__thumb-icon">📦</span>
                      <span class="commerce-cart-line__thumb-label">Sem foto</span>
                    </div>

                  <?php endif; ?>



                  <div class="commerce-cart-line__info">

                    <a class="commerce-cart-line__name"

                       href="<?= BASE_URL ?>/produtos/<?= e((string) $item['public_slug']) ?>">

                      <?= e((string) $item['name']) ?>

                    </a>

                    <p class="commerce-cart-line__meta">

                      Unitário: <?= formatarPreco((float) $item['unit_price']) ?>

                    </p>

                  </div>



                  <div class="commerce-cart-line__qty">

                    <label>Qtd</label>

                    <div class="commerce-qty-control" data-qty-control>
                      <div class="commerce-qty-control__group">
                        <button type="button" class="commerce-qty-control__btn" data-qty-delta="-1" aria-label="Diminuir quantidade">−</button>
                        <input
                          class="commerce-qty-control__input"
                          type="number"
                          name="quantities[<?= (int) $item['product_id'] ?>]"
                          min="0"
                          step="1"
                          value="<?= (int) $item['quantity'] ?>"
                          inputmode="numeric"
                          aria-label="Quantidade"
                          data-qty-input
                        />
                        <button type="button" class="commerce-qty-control__btn" data-qty-delta="1" aria-label="Aumentar quantidade">+</button>
                      </div>
                    </div>

                  </div>



                  <div class="commerce-cart-line__price">

                    <strong><?= formatarPreco((float) $item['total']) ?></strong>

                    <small>total</small>

                  </div>



                  <button class="commerce-cart-line__remove"

                          type="submit"

                          formaction="<?= BASE_URL ?>/carrinho/remover"

                          formmethod="POST"

                          name="product_id"

                          value="<?= (int) $item['product_id'] ?>"

                          aria-label="Remover item"><span aria-hidden="true">✕</span></button>

                </div>

              <?php endforeach; ?>

            </div>



            <div class="commerce-cart-actions">
              <a class="btn secondary" href="<?= BASE_URL ?>/produtos">Continuar comprando</a>
            </div>

          </div>



          <aside class="commerce-layout__aside">

            <div class="commerce-summary">

              <h2>Resumo</h2>

              <div class="commerce-summary__lines">

                <div class="commerce-summary__line">

                  <span><?= (int) $cart['item_count'] ?> item(ns)</span>

                </div>

              </div>

              <div class="commerce-summary__total">

                <span class="commerce-summary__total-label">Subtotal</span>

                <span class="commerce-summary__total-value"><?= formatarPreco((float) $cart['subtotal']) ?></span>

              </div>

              <div class="commerce-summary__actions">

                <a class="btn primary" href="<?= BASE_URL ?>/checkout">Continuar para o pedido</a>

              </div>

              <p class="commerce-summary__note">Valores e disponibilidade confirmados pela loja.</p>

            </div>

          </aside>

        </form>



        <div class="commerce-mobile-bar">

          <div class="commerce-mobile-bar__inner">

            <div class="commerce-mobile-bar__total">

              <span class="commerce-mobile-bar__label">Subtotal</span>

              <span class="commerce-mobile-bar__value"><?= formatarPreco((float) $cart['subtotal']) ?></span>

            </div>

            <a class="btn primary" href="<?= BASE_URL ?>/checkout">Continuar para o pedido</a>

          </div>

        </div>

      <?php endif; ?>

    </main>



    <?php commerce_footer_minimal($clientLegalName); ?>
    <?php site_nav_scripts(); ?>
    <?php commerce_assets(); ?>
  </body>

</html>

