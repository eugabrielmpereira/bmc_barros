<?php
/**
 * Busca do catálogo (toolbar e controles).
 *
 * @var string $activeCategorySlug
 * @var string $activeBrandSlug
 * @var list<string> $activeAttributeSlugs
 * @var string $searchQuery
 * @var string $formClass classes CSS adicionais
 */
$activeCategorySlug = $activeCategorySlug ?? '';
$activeBrandSlug = $activeBrandSlug ?? '';
$activeAttributeSlugs = $activeAttributeSlugs ?? [];
$searchQuery = $searchQuery ?? '';
$formClass = trim('catalog-search ' . ($formClass ?? ''));
?>
<form class="<?= e($formClass) ?>" method="GET" action="<?= e(app_url('/produtos')) ?>" role="search">
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
    <span class="visually-hidden">Buscar produtos</span>
    <input type="search" name="busca" placeholder="Buscar produto, SKU..." value="<?= e($searchQuery) ?>" />
    <button type="submit">Buscar</button>
  </label>
</form>
