<div id="catalog-filters-drawer" class="catalog-filters-drawer" hidden aria-hidden="true">
  <div class="catalog-filters-drawer__overlay" data-catalog-filters-close tabindex="-1"></div>
  <aside
    class="catalog-filters-drawer__panel"
    role="dialog"
    aria-modal="true"
    aria-labelledby="catalog-filters-drawer-title"
  >
    <header class="catalog-filters-drawer__header">
      <h2 id="catalog-filters-drawer-title">Filtros</h2>
      <button type="button" class="catalog-filters-drawer__close" data-catalog-filters-close aria-label="Fechar filtros">×</button>
    </header>
    <div class="catalog-filters-drawer__body">
      <?php require SHARED_PATH . '/View/catalog/sidebar-nav.php'; ?>
    </div>
  </aside>
</div>
