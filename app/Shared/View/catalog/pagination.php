<?php
/**
 * Paginação do catálogo público.
 *
 * @var array{page: int, per_page: int, total: int, total_pages: int} $pagination
 */
$pagination = $pagination ?? ['page' => 1, 'per_page' => 24, 'total' => 0, 'total_pages' => 1];
$page = (int) ($pagination['page'] ?? 1);
$totalPages = (int) ($pagination['total_pages'] ?? 1);
$total = (int) ($pagination['total'] ?? 0);

if ($totalPages <= 1) {
    return;
}
?>
<nav class="catalog-pagination" aria-label="Paginação do catálogo">
  <?php if ($page > 1): ?>
    <a class="btn secondary" href="<?= catalog_filter_url(['pagina' => (string) ($page - 1)]) ?>">← Anterior</a>
  <?php endif; ?>
  <span class="pagination-meta">Página <?= $page ?> de <?= $totalPages ?> · <?= $total ?> produtos</span>
  <?php if ($page < $totalPages): ?>
    <a class="btn secondary" href="<?= catalog_filter_url(['pagina' => (string) ($page + 1)]) ?>">Próxima →</a>
  <?php endif; ?>
</nav>
