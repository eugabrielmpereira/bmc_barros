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
    <header class="topbar">
      <a class="brand" href="<?= BASE_URL ?>" aria-label="Ir para o início">
        <img src="<?= BASE_URL . e((string) $client['assets']['logo']) ?>" alt="Logomarca <?= e($clientName) ?>" />
        <span><strong><?= e($clientName) ?></strong><small><?= e((string) ($client['tagline'] ?? '')) ?></small></span>
      </a>
      <button class="menu-btn" type="button" aria-label="Abrir menu" aria-expanded="false">☰</button>
      <nav class="nav">
        <a href="<?= BASE_URL ?>">Início</a>
        <a href="<?= BASE_URL ?>/produtos" class="active">Produtos</a>
        <?php commerce_cart_nav_link(); ?>
        <a href="<?= BASE_URL ?>/#sobre">Sobre</a>
        <a href="<?= BASE_URL ?>/#localizacao">Localização</a>
      </nav>
      <a class="whatsapp-top" href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Quero informações sobre o produto ' . $productName . '.')) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
    </header>

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

          <div class="availability availability-<?= e($availabilityStatus) ?>">
            <strong><?= e($availabilityLabel) ?></strong>
            <span><?= $stock > 0 ? e((string) $stock) . ' unidade(s) no estoque atual' : 'Confirme prazo e disponibilidade com a loja' ?></span>
          </div>

          <p class="product-detail-description"><?= e($description !== '' ? $description : 'Descrição não disponível para este produto.') ?></p>

          <form method="POST" action="<?= BASE_URL ?>/carrinho/adicionar" class="product-detail-actions" data-commerce-add>
            <?= csrf_field() ?>
            <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>" />
            <label>
              Quantidade
              <input class="form-control" type="number" name="quantity" min="1" step="1" value="1" />
            </label>
            <button class="btn primary" type="submit">Adicionar ao carrinho</button>
          </form>

          <div class="product-detail-actions">
            <a class="btn primary" href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Quero informações sobre o produto ' . $productName . '.')) ?>" target="_blank" rel="noreferrer">Solicitar pelo WhatsApp</a>
            <a class="btn secondary" href="<?= BASE_URL ?>/produtos">Voltar ao catálogo</a>
          </div>
        </div>
      </section>
    </main>

    <footer>
      <img src="<?= BASE_URL . e((string) $client['assets']['logo']) ?>" alt="Logomarca <?= e($clientName) ?>" />
      <div>
        <strong><?= e($clientLegalName) ?></strong>
        <p>Endereço: <?= e((string) $client['address']) ?></p>
        <p>WhatsApp e telefone: <a href="tel:<?= e((string) $client['phoneHref']) ?>"><?= e((string) $client['phone']) ?></a></p>
        <p>Seg-Sex: <?= e((string) $client['businessHours']['weekday']) ?> | Sábado: <?= e((string) $client['businessHours']['saturday']) ?> | Domingo: <?= e((string) $client['businessHours']['sunday']) ?></p>
      </div>
    </footer>

    <?php commerce_assets(); ?>
    <script src="<?= client_asset('js/script.js') ?>"></script>
  </body>
</html>
