<?php
/**
 * Menu hierárquico e filtros do catálogo público.
 *
 * @var list<array<string, mixed>> $categoryTree
 * @var list<array<string, mixed>> $brands
 * @var string $activeCategorySlug
 * @var string $activeBrandSlug
 * @var list<string> $activeAttributeSlugs
 * @var list<array<string, mixed>> $attributeFacets
 */
$categoryTree = $categoryTree ?? ($categorias ?? []);
$brands = $brands ?? [];
$attributeFacets = $attributeFacets ?? [];
$activeCategorySlug = $activeCategorySlug ?? '';
$activeBrandSlug = $activeBrandSlug ?? '';
$activeAttributeSlugs = $activeAttributeSlugs ?? [];
?>
<div class="sidebar-card">
  <h2>Categorias</h2>
  <nav class="sidebar-nav sidebar-nav-tree" aria-label="Categorias do catálogo">
    <a class="sidebar-link-root <?= $activeCategorySlug === '' ? 'active' : '' ?>" href="<?= catalog_filter_url(['categoria' => null]) ?>">Todos os produtos</a>
    <?php foreach ($categoryTree as $root): ?>
      <?php
        $rootSlug = (string) ($root['slug'] ?? '');
        $children = $root['children'] ?? [];
        $rootActive = $activeCategorySlug === $rootSlug;
        $childActive = false;
        foreach ($children as $child) {
            if ($activeCategorySlug === (string) ($child['slug'] ?? '')) {
                $childActive = true;
                break;
            }
        }
        $groupOpen = $rootActive || $childActive;
      ?>
      <?php if ($children !== []): ?>
        <div class="sidebar-tree-group<?= $groupOpen ? ' is-open' : '' ?>" data-sidebar-tree-group>
          <div class="sidebar-tree-row">
            <a class="sidebar-link-root<?= $rootActive ? ' active' : '' ?>" href="<?= catalog_filter_url(['categoria' => $rootSlug]) ?>">
              <?= e((string) $root['nome']) ?>
            </a>
            <button
              type="button"
              class="sidebar-tree-toggle"
              data-sidebar-tree-toggle
              aria-expanded="<?= $groupOpen ? 'true' : 'false' ?>"
              aria-label="<?= $groupOpen ? 'Recolher' : 'Expandir' ?> subcategorias de <?= e((string) $root['nome']) ?>"
            >
              <span class="sidebar-tree-toggle__icon" aria-hidden="true"></span>
            </button>
          </div>
          <div class="sidebar-subnav"<?= $groupOpen ? '' : ' hidden' ?>>
            <?php foreach ($children as $child): ?>
              <?php $childSlug = (string) ($child['slug'] ?? ''); ?>
              <a class="sidebar-link-child<?= $activeCategorySlug === $childSlug ? ' active' : '' ?>" href="<?= catalog_filter_url(['categoria' => $childSlug]) ?>">
                <?= e((string) $child['nome']) ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
      <?php else: ?>
        <a class="sidebar-link-root<?= $rootActive ? ' active' : '' ?>" href="<?= catalog_filter_url(['categoria' => $rootSlug]) ?>">
          <?= e((string) $root['nome']) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
</div>

<?php if ($brands !== []): ?>
  <div class="sidebar-card sidebar-card-brands">
    <h2>Marcas</h2>
    <nav class="brand-filter-nav" aria-label="Filtrar por marca">
      <a class="<?= $activeBrandSlug === '' ? 'active' : '' ?>" href="<?= catalog_filter_url(['marca' => null]) ?>">Todas</a>
      <?php foreach ($brands as $brand): ?>
        <?php $brandSlug = (string) ($brand['slug'] ?? ''); ?>
        <a class="<?= $activeBrandSlug === $brandSlug ? 'active' : '' ?>" href="<?= catalog_filter_url(['marca' => $brandSlug]) ?>">
          <?= e((string) $brand['name']) ?>
          <span class="brand-count"><?= (int) ($brand['product_count'] ?? 0) ?></span>
        </a>
      <?php endforeach; ?>
    </nav>
  </div>
<?php endif; ?>

<?php if ($attributeFacets !== []): ?>
  <div class="sidebar-card sidebar-card-attributes">
    <h2>Especificações</h2>
    <?php foreach ($attributeFacets as $facet): ?>
      <div class="attribute-facet-group">
        <h3><?= e((string) $facet['name']) ?></h3>
        <nav class="attribute-filter-nav" aria-label="Filtro <?= e((string) $facet['name']) ?>">
          <?php foreach ($facet['options'] as $option): ?>
            <?php
              $optionSlug = (string) $option['slug'];
              $isActive = in_array($optionSlug, $activeAttributeSlugs, true);
              $nextSlugs = $isActive
                  ? array_values(array_filter($activeAttributeSlugs, static fn (string $s): bool => $s !== $optionSlug))
                  : array_merge($activeAttributeSlugs, [$optionSlug]);
            ?>
            <a class="<?= $isActive ? 'active' : '' ?>" href="<?= catalog_filter_url(['atributo' => $nextSlugs, 'pagina' => null]) ?>">
              <?= e((string) $option['value']) ?>
              <span class="brand-count"><?= (int) $option['product_count'] ?></span>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>
    <?php endforeach; ?>
    <?php if ($activeAttributeSlugs !== []): ?>
      <a class="attribute-clear" href="<?= catalog_filter_url(['atributo' => null, 'pagina' => null]) ?>">Limpar especificações</a>
    <?php endif; ?>
  </div>
<?php endif; ?>
