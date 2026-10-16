<?php
$client = $client ?? client_config();
$cart = $cart ?? ['items' => [], 'item_count' => 0, 'subtotal' => 0.0];
$items = $cart['items'] ?? [];
$msg = $msg ?? null;
$tipo = $tipo ?? 'ok';
$subtotal = (float) ($cart['subtotal'] ?? 0);
$itemCount = (int) ($cart['item_count'] ?? 0);

$pageTitle = 'Carrinho';
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
        <div class="page-head">
          <h1 class="display">Carrinho</h1>
        </div>

        <?php if ($msg): ?>
          <div class="commerce-alert commerce-alert--<?= e(commerce_alert_modifier((string) $tipo)) ?>" role="status">
            <p><?= e((string) $msg) ?></p>
          </div>
        <?php endif; ?>

        <?php if ($items === []): ?>
          <div class="surface catalog-empty">
            <h2>Seu carrinho está vazio</h2>
            <p>Encontre o que falta para a sua obra no catálogo.</p>
            <div class="hero__actions">
              <a class="btn btn--primary" href="<?= e(app_url('/produtos')) ?>">Ver produtos</a>
            </div>
          </div>
        <?php else: ?>
          <div class="checkout-layout">
            <form method="POST" action="<?= e(app_url('/carrinho/atualizar')) ?>" data-commerce-cart-update>
              <?= csrf_field() ?>
              <div class="surface cart-lines">
                <?php foreach ($items as $item): ?>
                  <?php $itemUrl = app_url('/produtos/' . rawurlencode((string) $item['public_slug'])); ?>
                  <div class="cart-line">
                    <div class="cart-line__thumb">
                      <?php if (!empty($item['image'])): ?>
                        <img src="<?= e(client_product_image_url((string) $item['image'])) ?>" alt="" />
                      <?php else: ?>
                        <?= bmc_icon(bmc_department_icon((string) $item['name'])) ?>
                      <?php endif; ?>
                    </div>
                    <div class="cart-line__info">
                      <a class="cart-line__name" href="<?= e($itemUrl) ?>"><?= e((string) $item['name']) ?></a>
                      <p class="cart-line__unit"><?= formatarPreco((float) $item['unit_price']) ?> cada</p>
                    </div>
                    <div class="cart-line__qty commerce-cart-line__qty">
                      <div class="commerce-qty-control" data-qty-control>
                        <div class="commerce-qty-control__group">
                          <button type="button" class="commerce-qty-control__btn" data-qty-delta="-1" aria-label="Diminuir quantidade de <?= e((string) $item['name']) ?>">−</button>
                          <input class="commerce-qty-control__input" type="number" name="quantities[<?= (int) $item['product_id'] ?>]" min="0" step="1" value="<?= (int) $item['quantity'] ?>" inputmode="numeric" aria-label="Quantidade de <?= e((string) $item['name']) ?>" data-qty-input />
                          <button type="button" class="commerce-qty-control__btn" data-qty-delta="1" aria-label="Aumentar quantidade de <?= e((string) $item['name']) ?>">+</button>
                        </div>
                      </div>
                    </div>
                    <p class="cart-line__total"><?= formatarPreco((float) $item['total']) ?></p>
                    <button class="cart-line__remove commerce-cart-line__remove" type="submit" formaction="<?= e(app_url('/carrinho/remover')) ?>" formmethod="POST" name="product_id" value="<?= (int) $item['product_id'] ?>" aria-label="Remover <?= e((string) $item['name']) ?>">
                      <?= bmc_icon('trash') ?>
                    </button>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="cart-actions">
                <a class="btn btn--ghost" href="<?= e(app_url('/produtos')) ?>">Continuar comprando</a>
                <noscript><button class="btn" type="submit">Atualizar quantidades</button></noscript>
              </div>
            </form>

            <aside class="surface summary" aria-labelledby="resumo-titulo">
              <h2 id="resumo-titulo">Resumo</h2>
              <div class="summary__row"><span><?= $itemCount ?> <?= $itemCount === 1 ? 'item' : 'itens' ?></span><span><?= formatarPreco($subtotal) ?></span></div>
              <div class="summary__row"><span>Frete</span><span>Calculado no próximo passo</span></div>
              <div class="summary__row summary__row--total"><span>Subtotal</span><span><?= formatarPreco($subtotal) ?></span></div>
              <a class="btn btn--primary btn--lg btn--block" href="<?= e(app_url('/checkout')) ?>">Fechar pedido</a>
              <p class="summary__note">Retirada na loja não tem custo.</p>
            </aside>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <?php if ($items !== []): ?>
      <div class="mobile-bar">
        <div class="mobile-bar__total"><span>Subtotal</span><strong><?= formatarPreco($subtotal) ?></strong></div>
        <a class="btn btn--primary" href="<?= e(app_url('/checkout')) ?>">Fechar pedido</a>
      </div>
    <?php endif; ?>

    <?php commerce_footer_minimal(); ?>
    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
