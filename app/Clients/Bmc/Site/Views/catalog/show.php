<?php
use Erp\Finance\Support\PaymentMethod;

$product = $product ?? [];
$related = $related ?? [];
$zones = $zones ?? [];
$client = $client ?? client_config();
$storeName = (string) ($client['name'] ?? 'Loja');
$productName = (string) ($product['nome'] ?? 'Produto');
$longDescription = trim((string) ($product['long_description'] ?? ''));
$shortDescription = trim((string) ($product['descricao'] ?? ''));
$description = $longDescription !== '' ? $longDescription : $shortDescription;
$stock = (int) ($product['estoque_atual'] ?? 0);
$price = isset($product['preco']) ? (float) $product['preco'] : null;
$compareAt = isset($product['compare_at_price']) && $product['compare_at_price'] !== null ? (float) $product['compare_at_price'] : null;
$brand = trim((string) ($product['brand_name'] ?? ''));
$sku = trim((string) ($product['sku'] ?? ''));
$category = (string) ($product['categoria_nome'] ?? '');
$isLow = (string) ($product['availability_status'] ?? '') === 'low_stock';
$productPath = '/produtos/' . rawurlencode((string) ($product['public_slug'] ?? ''));
$cardEnabled = PaymentMethod::cardEnabled();
$installmentOptions = $cardEnabled && $price !== null ? PaymentMethod::installmentOptions($price) : [];
$bestInstallment = $installmentOptions !== [] ? end($installmentOptions) : null;
$whatsUrl = client_whatsapp_url('Olá, ' . $storeName . '! Quero saber sobre o produto ' . $productName . '.');

$cheapestZone = null;
foreach ($zones as $zone) {
    if ($cheapestZone === null || (float) $zone['fee'] < (float) $cheapestZone['fee']) {
        $cheapestZone = $zone;
    }
}

$pageTitle = (string) (($product['meta_title'] ?? '') ?: $productName);
$pageDescription = (string) (($product['meta_description'] ?? '') ?: ($shortDescription !== '' ? $shortDescription : $productName . ' na ' . $storeName . '.'));
$canonicalPath = $productPath;
$ogType = 'product';
if (!empty($product['imagem'])) {
    $ogImageUrl = client_product_image_url((string) $product['imagem']);
}
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $productName,
    'description' => $pageDescription,
    'sku' => $sku !== '' ? $sku : (string) ($product['id'] ?? ''),
    'category' => $category,
    'url' => app_url($productPath),
];
if (!empty($product['imagem'])) {
    $jsonLd['image'] = client_product_image_url((string) $product['imagem']);
}
if ($brand !== '') {
    $jsonLd['brand'] = ['@type' => 'Brand', 'name' => $brand];
}
if ($price !== null && $price > 0) {
    $jsonLd['offers'] = [
        '@type' => 'Offer',
        'priceCurrency' => 'BRL',
        'price' => number_format($price, 2, '.', ''),
        'availability' => $stock > 0 ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => app_url($productPath),
        'seller' => ['@type' => 'Organization', 'name' => (string) ($client['legalName'] ?? $storeName)],
    ];
}
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php require __DIR__ . '/../partials/head.php'; ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="page">
      <div class="wrap">
        <nav class="breadcrumb" aria-label="Você está em">
          <a href="<?= e(app_url()) ?>">Início</a>
          <?= bmc_icon('chevron') ?>
          <a href="<?= e(app_url('/produtos')) ?>">Produtos</a>
          <?php foreach (($product['breadcrumbs'] ?? []) as $crumb): ?>
            <?= bmc_icon('chevron') ?>
            <a href="<?= catalog_filter_url(['categoria' => (string) $crumb['slug']]) ?>"><?= e((string) $crumb['nome']) ?></a>
          <?php endforeach; ?>
        </nav>

        <div class="pdp">
          <div class="pdp__media">
            <?php if (!empty($product['imagem'])): ?>
              <img src="<?= e(client_product_image_url((string) $product['imagem'])) ?>" alt="<?= e($productName) ?>" />
            <?php else: ?>
              <?= bmc_icon(bmc_department_icon($category . ' ' . $productName), 'icon icon--xl') ?>
            <?php endif; ?>
          </div>

          <div class="surface pdp__info">
            <p class="pdp__meta"><?= e($brand !== '' ? $brand . ', ' . mb_strtolower($category, 'UTF-8') : $category) ?></p>
            <h1 class="pdp__title"><?= e($productName) ?></h1>
            <?php if ($sku !== ''): ?>
              <p class="pdp__sku">Código <?= e($sku) ?></p>
            <?php endif; ?>

            <div class="pdp__price"><?= bmc_price_tag($price, 'lg', $compareAt) ?></div>
            <?php if ($bestInstallment !== null && $bestInstallment['count'] > 1): ?>
              <p class="pdp__installments">ou <?= e($bestInstallment['label']) ?> no cartão</p>
            <?php elseif ($price !== null && $price > 0): ?>
              <p class="pdp__installments">À vista no Pix</p>
            <?php endif; ?>

            <?php if ($stock > 0): ?>
              <p class="stock-line<?= $isLow ? ' stock-line--low' : '' ?>">
                <?= $isLow ? 'Últimas ' . $stock . ' unidades em estoque' : 'Em estoque' ?>
              </p>

              <form method="POST" action="<?= e(app_url('/carrinho/adicionar')) ?>" class="pdp__buy" data-commerce-product-form>
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>" />
                <div class="commerce-qty-control" data-qty-control>
                  <label class="commerce-qty-control__label" for="pdp-qty">Quantidade</label>
                  <div class="commerce-qty-control__group">
                    <button type="button" class="commerce-qty-control__btn" data-qty-delta="-1" aria-label="Diminuir quantidade">−</button>
                    <input id="pdp-qty" class="commerce-qty-control__input" type="number" name="quantity" min="1" max="<?= $stock ?>" data-stock-max="<?= $stock ?>" step="1" value="1" inputmode="numeric" data-qty-input />
                    <button type="button" class="commerce-qty-control__btn" data-qty-delta="1" aria-label="Aumentar quantidade">+</button>
                  </div>
                </div>
                <button class="btn btn--primary btn--lg" type="submit" data-commerce-add-cart><?= bmc_icon('cart') ?> Adicionar ao carrinho</button>
                <button class="btn btn--lg pdp__buy-now" type="submit" name="buy_now" value="1" data-commerce-buy-now>Comprar agora</button>
              </form>
            <?php else: ?>
              <p class="stock-line stock-line--out">Sem estoque no momento</p>
              <form method="POST" action="<?= e(app_url('/produtos/avisar-estoque')) ?>" class="stock-alert" data-stock-alert-form>
                <?= csrf_field() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>" />
                <input type="hidden" name="return_to" value="<?= e($productPath) ?>" />
                <label for="stock-alert-email">Avise-me quando chegar</label>
                <input id="stock-alert-email" class="form-control" type="email" name="email" placeholder="seu@email.com" required autocomplete="email" />
                <button class="btn btn--primary" type="submit">Quero ser avisado</button>
              </form>
            <?php endif; ?>

            <ul class="pdp__perks">
              <li>
                <?= bmc_icon('truck') ?>
                <div>
                  <strong>Entrega com caminhão da loja</strong>
                  <span>
                    <?php if ($cheapestZone !== null): ?>
                      Frete a partir de <?= (float) $cheapestZone['fee'] > 0 ? e(formatarPreco((float) $cheapestZone['fee'])) : 'R$ 0,00' ?>. Valor exato no fechamento, pelo seu bairro.
                    <?php else: ?>
                      Frete combinado com a loja após o pedido.
                    <?php endif; ?>
                  </span>
                </div>
              </li>
              <li>
                <?= bmc_icon('store') ?>
                <div><strong>Retirada na loja sem custo</strong><span><?= e((string) ($client['address'] ?? '')) ?></span></div>
              </li>
              <li>
                <?= bmc_icon('whatsapp') ?>
                <div><strong>Dúvida sobre medida ou quantidade?</strong><span><a href="<?= e($whatsUrl) ?>" target="_blank" rel="noreferrer">Pergunte no WhatsApp</a></span></div>
              </li>
            </ul>
          </div>
        </div>

        <?php if ($description !== ''): ?>
          <section class="surface pdp__description" aria-labelledby="descricao-titulo">
            <h2 id="descricao-titulo">Descrição</h2>
            <p><?= e($description) ?></p>
          </section>
        <?php endif; ?>

        <?php if ($related !== []): ?>
          <section class="section section--flush-bottom" aria-labelledby="relacionados-titulo">
            <div class="section-head">
              <h2 id="relacionados-titulo">Mais em <?= e(mb_strtolower($category, 'UTF-8')) ?></h2>
              <a href="<?= catalog_filter_url(['categoria' => (string) ($product['categoria_slug'] ?? '')]) ?>">Ver todos</a>
            </div>
            <div class="product-grid">
              <?php foreach ($related as $produto): ?>
                <?php require __DIR__ . '/../partials/product-card.php'; ?>
              <?php endforeach; ?>
            </div>
          </section>
        <?php endif; ?>
      </div>
    </main>

    <?php site_store_footer($client); ?>
    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
