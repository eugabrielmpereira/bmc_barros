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
$storeName = (string) ($client['name'] ?? 'Loja');
$total = (int) ($pagination['total'] ?? count($produtos));

if ($searchQuery !== '') {
    $heading = 'Resultados para "' . $searchQuery . '"';
} elseif ($selectedCategoria) {
    $heading = (string) $selectedCategoria['nome'];
} elseif ($selectedBrand) {
    $heading = 'Produtos ' . (string) $selectedBrand['name'];
} else {
    $heading = 'Todos os produtos';
}

$pageTitle = $selectedCategoria
    ? (string) (($selectedCategoria['meta_title'] ?? '') ?: $selectedCategoria['nome'])
    : ($searchQuery !== '' ? 'Busca: ' . $searchQuery : 'Produtos');
$pageDescription = $selectedCategoria && !empty($selectedCategoria['meta_description'])
    ? (string) $selectedCategoria['meta_description']
    : $heading . ' na ' . $storeName . ' com entrega em ' . (string) ($client['serviceArea'] ?? 'na região') . '.';
$canonicalPath = '/produtos' . ($activeCategorySlug !== '' ? '?categoria=' . rawurlencode($activeCategorySlug) : '');
$noindex = $searchQuery !== '' || $activeAttributeSlugs !== [];
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
          <?php foreach ($breadcrumbs as $crumb): ?>
            <?= bmc_icon('chevron') ?>
            <a href="<?= catalog_filter_url(['categoria' => (string) $crumb['slug'], 'marca' => $activeBrandSlug !== '' ? $activeBrandSlug : null]) ?>"><?= e((string) $crumb['nome']) ?></a>
          <?php endforeach; ?>
        </nav>

        <div class="page-head">
          <h1 class="display"><?= e($heading) ?></h1>
          <?php if ($selectedCategoria && !empty($selectedCategoria['description'])): ?>
            <p><?= e((string) $selectedCategoria['description']) ?></p>
          <?php endif; ?>
        </div>

        <div class="catalog">
          <aside class="catalog__aside" aria-label="Filtros do catálogo">
            <?php require SHARED_PATH . '/View/catalog/sidebar-nav.php'; ?>
          </aside>

          <div>
            <div class="catalog__toolbar">
              <p class="catalog__count"><?= $total ?> <?= $total === 1 ? 'produto' : 'produtos' ?><?= $selectedBrand ? ' da marca ' . e((string) $selectedBrand['name']) : '' ?></p>
              <button type="button" class="btn btn--ghost catalog__filters-btn" data-catalog-filters-open aria-controls="catalog-filters-drawer" aria-expanded="false">
                <?= bmc_icon('filter') ?> Filtrar
              </button>
            </div>

            <?php if ($produtos === []): ?>
              <div class="surface catalog-empty">
                <h2>Nenhum produto encontrado</h2>
                <p>Tente outra palavra na busca ou remova algum filtro. Se não achar o que precisa, a loja pode encomendar.</p>
                <div class="hero__actions">
                  <a href="<?= e(app_url('/produtos')) ?>" class="btn btn--ghost">Ver todos os produtos</a>
                  <a href="<?= e(client_whatsapp_url('Olá, ' . $storeName . '! Procuro um produto que não encontrei no site' . ($searchQuery !== '' ? ': ' . $searchQuery : '') . '.')) ?>" class="btn btn--whats" target="_blank" rel="noreferrer"><?= bmc_icon('whatsapp') ?> Perguntar no WhatsApp</a>
                </div>
              </div>
            <?php else: ?>
              <div class="product-grid product-grid--catalog">
                <?php foreach ($produtos as $produto): ?>
                  <?php require __DIR__ . '/../partials/product-card.php'; ?>
                <?php endforeach; ?>
              </div>
              <?php require SHARED_PATH . '/View/catalog/pagination.php'; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>

    <?php site_store_footer($client); ?>
    <?php require SHARED_PATH . '/View/catalog/filters-drawer.php'; ?>
    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
