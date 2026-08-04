<?php
$product = $product ?? [];
$client = $client ?? client_config();
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
$productName = (string) ($product['nome'] ?? 'Produto');
$description = trim((string) ($product['descricao'] ?? ''));
$availabilityStatus = (string) ($product['availability_status'] ?? 'on_request');
$availabilityLabel = (string) ($product['availability_label'] ?? 'Consultar disponibilidade');
$stock = (int) ($product['estoque_atual'] ?? 0);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($productName) ?> no catálogo da <?= e($clientLegalName) ?>." />
    <title><?= e($productName) ?> | <?= e($clientName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
  </head>
  <body>
    <?php site_topbar([
        'client' => $client,
        'whatsappMessage' => 'Olá, ' . $clientName . '! Quero informações sobre o produto ' . $productName . '.',
    ]); ?>

    <main class="catalog-page">
      <nav class="breadcrumb" aria-label="Caminho da página">
        <a href="<?= BASE_URL ?>">Início</a>
        <span>/</span>
        <a href="<?= BASE_URL ?>/produtos">Produtos</a>
        <?php foreach (($product['breadcrumbs'] ?? []) as $crumb): ?>
          <span>/</span>
          <a href="<?= catalog_filter_url(['categoria' => (string) $crumb['slug']]) ?>"><?= e((string) $crumb['nome']) ?></a>
        <?php endforeach; ?>
        <span>/</span>
        <strong><?= e($productName) ?></strong>
      </nav>

      <section class="product-detail">
        <div class="product-detail-media">
          <?php if (!empty($product['imagem'])): ?>
            <img src="<?= e(client_product_image_url((string) $product['imagem'])) ?>" alt="<?= e($productName) ?>" />
          <?php else: ?>
            <div class="product-detail-empty">Sem imagem</div>
          <?php endif; ?>
        </div>

        <div class="product-detail-info">
          <span class="product-category"><?= e((string) ($product['categoria_nome'] ?? 'Categoria')) ?></span>
          <h1><?= e($productName) ?></h1>
          <strong class="product-detail-price"><?= formatarPreco(isset($product['preco']) ? (float) $product['preco'] : null) ?></strong>

          <div class="availability availability-<?= $stock > 0 ? e($availabilityStatus) : 'unavailable' ?>">
            <strong><?= $stock > 0 ? e($availabilityLabel) : 'Indisponível' ?></strong>
            <span><?= $stock > 0 ? e((string) $stock) . ' unidade(s) no estoque atual' : 'Produto sem estoque no momento' ?></span>
          </div>

          <p class="product-detail-description"><?= e($description !== '' ? $description : 'Descrição não disponível para este produto.') ?></p>

          <?php if ($stock > 0): ?>
            <form method="POST" action="<?= BASE_URL ?>/carrinho/adicionar" class="product-detail-commerce" data-commerce-product-form>
              <?= csrf_field() ?>
              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>" />

              <div class="commerce-qty-control" data-qty-control>
                <span class="commerce-qty-control__label">Quantidade</span>
                <div class="commerce-qty-control__group">
                  <button type="button" class="commerce-qty-control__btn" data-qty-delta="-1" aria-label="Diminuir quantidade">−</button>
                  <input
                    class="commerce-qty-control__input"
                    type="number"
                    name="quantity"
                    min="1"
                    max="<?= $stock ?>"
                    data-stock-max="<?= $stock ?>"
                    step="1"
                    value="1"
                    inputmode="numeric"
                    aria-label="Quantidade"
                    data-qty-input
                  />
                  <button type="button" class="commerce-qty-control__btn" data-qty-delta="1" aria-label="Aumentar quantidade">+</button>
                </div>
              </div>

              <div class="product-detail-cta-stack">
                <button class="btn product-detail-cta product-detail-cta--buy" type="submit" name="buy_now" value="1" data-commerce-buy-now>
                  Comprar agora
                </button>
                <button class="btn product-detail-cta product-detail-cta--cart" type="submit" data-commerce-add-cart>
                  Adicionar ao carrinho
                </button>
              </div>
            </form>
          <?php else: ?>
            <form method="POST" action="<?= BASE_URL ?>/produtos/avisar-estoque" class="product-stock-alert-form" data-stock-alert-form>
              <?= csrf_field() ?>
              <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>" />
              <input type="hidden" name="return_to" value="<?= e((string) ($_SERVER['REQUEST_URI'] ?? '/produtos')) ?>" />
              <label class="commerce-qty-control__label" for="stock-alert-email">Me avise quando chegar</label>
              <input id="stock-alert-email" class="form-control" type="email" name="email" placeholder="seu@email.com" required />
              <button class="btn primary mt-2" type="submit">Cadastrar aviso</button>
            </form>
          <?php endif; ?>

          <div class="product-detail-secondary">
            <a
              class="product-detail-whatsapp-link"
              href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Quero informações sobre o produto ' . $productName . '.')) ?>"
              target="_blank"
              rel="noreferrer"
            >Falar no WhatsApp</a>
            <a class="btn secondary" href="<?= BASE_URL ?>/produtos">Voltar ao catálogo</a>
          </div>
        </div>
      </section>
    </main>

    <?php site_store_footer($client); ?>

    <?php site_nav_scripts(); ?>
    <?php commerce_assets(); ?>
  </body>
</html>
