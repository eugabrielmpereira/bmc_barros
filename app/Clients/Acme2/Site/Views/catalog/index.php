<?php
$produtos = $produtos ?? [];
$categoryTree = $categoryTree ?? ($categorias ?? []);
$brands = $brands ?? [];
$selectedCategoria = $selectedCategoria ?? null;
$selectedBrand = $selectedBrand ?? null;
$breadcrumbs = $breadcrumbs ?? [];
$activeCategorySlug = $activeCategorySlug ?? '';
$activeBrandSlug = $activeBrandSlug ?? '';
$activeAttributeSlugs = $activeAttributeSlugs ?? [];
$attributeFacets = $attributeFacets ?? [];
$pagination = $pagination ?? ['page' => 1, 'per_page' => 24, 'total' => 0, 'total_pages' => 1];
$searchQuery = $searchQuery ?? '';
$client = $client ?? client_config();
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
$pageTitle = $selectedCategoria
    ? (string) ($selectedCategoria['meta_title'] ?? $selectedCategoria['nome'])
    : 'Produtos';
$pageDescription = $selectedCategoria
    ? (string) ($selectedCategoria['meta_description'] ?? "Catálogo de produtos da {$clientLegalName}.")
    : "Catálogo de produtos da {$clientLegalName}.";
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($pageDescription) ?>" />
    <title><?= e($pageTitle) ?> | <?= e($clientName) ?></title>
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
      <a class="whatsapp-top" href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Vim pelo site e gostaria de atendimento.')) ?>" target="_blank" rel="noreferrer">WhatsApp</a>
    </header>

    <main class="catalog-page">
      <nav class="breadcrumb" aria-label="Caminho da página">
        <a href="<?= BASE_URL ?>">Início</a>
        <span>/</span>
        <a href="<?= BASE_URL ?>/produtos">Produtos</a>
        <?php foreach ($breadcrumbs as $crumb): ?>
          <span>/</span>
          <a href="<?= catalog_filter_url(['categoria' => (string) $crumb['slug'], 'marca' => $activeBrandSlug !== '' ? $activeBrandSlug : null]) ?>"><?= e((string) $crumb['nome']) ?></a>
        <?php endforeach; ?>
        <?php if ($selectedBrand !== null): ?>
          <span>/</span>
          <strong><?= e((string) $selectedBrand['name']) ?></strong>
        <?php endif; ?>
      </nav>

      <section class="catalog-hero catalog-hero-compact">
        <div class="catalog-title">
          <p class="eyebrow">Catálogo comercial</p>
          <h1><?= $selectedCategoria ? e((string) $selectedCategoria['nome']) : 'Produtos ' . e($clientName) ?></h1>
          <p><?= $selectedCategoria && !empty($selectedCategoria['description']) ? e((string) $selectedCategoria['description']) : 'Produtos ativos para compra e orçamento. Filtre por departamento, subcategoria ou marca.' ?></p>
        </div>
        <div class="catalog-hero-actions">
          <a href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Quero informações sobre produtos.')) ?>" class="btn primary" target="_blank" rel="noreferrer">Solicitar pelo WhatsApp</a>
        </div>
      </section>

      <section class="catalog-layout">
        <aside class="catalog-sidebar">
          <?php require SHARED_PATH . '/View/catalog/sidebar-nav.php'; ?>
        </aside>

        <div class="catalog-main">
          <div class="catalog-controls">
            <div>
              <p class="eyebrow">Seção</p>
              <h2>
                <?php if ($selectedCategoria): ?>
                  <?= e((string) $selectedCategoria['nome']) ?>
                <?php elseif ($selectedBrand): ?>
                  Marca: <?= e((string) $selectedBrand['name']) ?>
                <?php else: ?>
                  Todos os produtos
                <?php endif; ?>
              </h2>
            </div>
            <div class="catalog-meta">
              <span><?= (int) ($pagination['total'] ?? count($produtos)) ?> produtos encontrados</span>
              <form class="catalog-search" method="GET" action="<?= BASE_URL ?>/produtos">
                <?php if ($activeCategorySlug !== ''): ?>
                  <input type="hidden" name="categoria" value="<?= e($activeCategorySlug) ?>" />
                <?php endif; ?>
                <?php if ($activeBrandSlug !== ''): ?>
                  <input type="hidden" name="marca" value="<?= e($activeBrandSlug) ?>" />
                <?php endif; ?>
                <?php foreach ($activeAttributeSlugs as $attrSlug): ?>
                  <input type="hidden" name="atributo[]" value="<?= e($attrSlug) ?>" />
                <?php endforeach; ?>
                <label class="search-label">
                  <input type="search" name="busca" placeholder="Buscar produto, SKU..." value="<?= e($searchQuery) ?>" />
                  <button type="submit">Buscar</button>
                </label>
              </form>
            </div>
          </div>

          <?php if (empty($produtos)): ?>
            <div class="catalog-empty">
              <p>Nenhum produto ativo encontrado para os filtros selecionados.</p>
              <a href="<?= BASE_URL ?>/produtos" class="btn secondary">Ver todos os produtos</a>
            </div>
          <?php else: ?>
            <div class="product-grid">
              <?php foreach ($produtos as $produto): ?>
                <?php
                  $productUrl = BASE_URL . '/produtos/' . e((string) $produto['public_slug']);
                  $availabilityStatus = (string) ($produto['availability_status'] ?? 'on_request');
                  $availabilityLabel = (string) ($produto['availability_label'] ?? 'Consultar disponibilidade');
                ?>
                <article class="product-card">
                  <?php if (!empty($produto['imagem'])): ?>
                    <a class="product-image-link" href="<?= $productUrl ?>" aria-label="Ver detalhes de <?= e((string) $produto['nome']) ?>">
                      <img src="<?= e(client_product_image_url((string) $produto['imagem'])) ?>" alt="<?= e($produto['nome']) ?>" />
                    </a>
                  <?php else: ?>
                    <a class="product-image-link" href="<?= $productUrl ?>" aria-label="Ver detalhes de <?= e((string) $produto['nome']) ?>">
                      <div class="product-thumb-empty">Sem imagem</div>
                    </a>
                  <?php endif; ?>
                  <div class="product-details">
                    <span class="product-category"><?= e($produto['categoria_nome']) ?></span>
                    <?php if (!empty($produto['brand_name'])): ?>
                      <span class="product-brand"><?= e((string) $produto['brand_name']) ?></span>
                    <?php endif; ?>
                    <h3><a href="<?= $productUrl ?>"><?= e($produto['nome']) ?></a></h3>
                    <span class="availability-badge availability-<?= e($availabilityStatus) ?>"><?= e($availabilityLabel) ?></span>
                    <p><?= e($produto['descricao'] ?: 'Descrição não disponível.') ?></p>
                  </div>
                  <div class="product-footer">
                    <strong class="product-price"><?= formatarPreco(isset($produto['preco']) ? (float)$produto['preco'] : null) ?></strong>
                    <div class="product-actions">
                      <a class="btn secondary" href="<?= $productUrl ?>">Ver detalhes</a>
                      <form method="POST" action="<?= BASE_URL ?>/carrinho/adicionar" data-commerce-add>
                        <?= csrf_field() ?>
                        <input type="hidden" name="product_id" value="<?= (int) $produto['id'] ?>" />
                        <input type="hidden" name="quantity" value="1" />
                        <button class="btn primary" type="submit">Adicionar</button>
                      </form>
                      <a class="btn primary whatsapp-btn" href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Quero informações sobre o produto ' . (string) $produto['nome'])) ?>" target="_blank" rel="noreferrer">Solicitar pelo WhatsApp</a>
                    </div>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
            <?php require SHARED_PATH . '/View/catalog/pagination.php'; ?>
          <?php endif; ?>
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
