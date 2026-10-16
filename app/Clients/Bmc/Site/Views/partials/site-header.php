<?php
/**
 * Cabeçalho da loja BMC (substitui o topbar compartilhado via client_partial_override).
 */
require_once __DIR__ . '/icons.php';

$client = $client ?? client_config();
$storeName = (string) ($client['name'] ?? 'Loja');
$cartCount = commerce_cart_nav_count();
$departments = catalog_department_tree();
$currentPath = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
$basePath = (string) (parse_url((string) BASE_URL, PHP_URL_PATH) ?: '');
if ($basePath !== '' && str_starts_with($currentPath, $basePath)) {
    $currentPath = substr($currentPath, strlen($basePath)) ?: '/';
}
$activeDept = (string) ($_GET['categoria'] ?? '');
$searchValue = (string) ($_GET['busca'] ?? '');
$isLogged = customer_is_logged_in();
$displayName = $isLogged ? customer_display_name() : '';
$firstName = $displayName !== '' ? explode(' ', $displayName)[0] : 'Minha conta';
?>
<a class="skip-link" href="#conteudo">Pular para o conteúdo</a>
<header class="site-header">
  <div class="site-header__bar wrap">
    <button class="icon-btn menu-toggle" type="button" aria-controls="dept-nav" aria-expanded="false" aria-label="Abrir departamentos" data-menu-toggle>
      <?= bmc_icon('menu') ?>
    </button>

    <a class="brand" href="<?= e(app_url()) ?>" aria-label="<?= e($storeName) ?> — página inicial">
      <img class="brand__logo" src="<?= e(app_url((string) $client['assets']['logo'])) ?>" alt="" width="48" height="48" />
      <span class="brand__name"><?= e($storeName) ?><small><?= e((string) ($client['tagline'] ?? '')) ?></small></span>
    </a>

    <form class="header-search" method="GET" action="<?= e(app_url('/produtos')) ?>" role="search">
      <label class="visually-hidden" for="header-search-input">Buscar produtos</label>
      <input id="header-search-input" type="search" name="busca" value="<?= e($searchValue) ?>" placeholder="Buscar cimento, tubo, tinta…" autocomplete="off" />
      <button type="submit" aria-label="Buscar"><?= bmc_icon('search') ?></button>
    </form>

    <nav class="header-actions" aria-label="Conta e carrinho">
      <a class="header-action" href="<?= e(app_url($isLogged ? '/meus-pedidos' : '/entrar')) ?>">
        <?= bmc_icon('user') ?>
        <span class="header-action__label"><?= $isLogged ? e($firstName) : 'Entrar' ?></span>
      </a>
      <a class="header-action cart-nav-link" href="<?= e(app_url('/carrinho')) ?>" data-cart-drawer-trigger aria-label="Carrinho<?= $cartCount > 0 ? ', ' . $cartCount . ' itens' : '' ?>">
        <?= bmc_icon('cart') ?>
        <span class="header-action__label">Carrinho</span>
        <span class="cart-nav-badge<?= $cartCount <= 0 ? ' hidden' : '' ?>" data-cart-badge aria-live="polite"><?= $cartCount > 99 ? '99+' : (string) $cartCount ?></span>
      </a>
    </nav>
  </div>

  <nav class="dept-nav" id="dept-nav" aria-label="Departamentos" data-dept-nav>
    <div class="wrap dept-nav__inner">
      <a class="dept-nav__link<?= $currentPath === '/produtos' && $activeDept === '' ? ' is-active' : '' ?>" href="<?= e(app_url('/produtos')) ?>">Todos os produtos</a>
      <?php foreach ($departments as $dept): ?>
        <?php $slug = (string) ($dept['slug'] ?? ''); ?>
        <a class="dept-nav__link<?= $activeDept === $slug ? ' is-active' : '' ?>" href="<?= e(app_url('/produtos?categoria=' . rawurlencode($slug))) ?>"><?= e((string) $dept['nome']) ?></a>
      <?php endforeach; ?>
      <span class="dept-nav__spacer" aria-hidden="true"></span>
      <a class="dept-nav__link dept-nav__link--muted" href="<?= e(app_url('/acompanhar-pedido')) ?>">Acompanhar pedido</a>
      <a class="dept-nav__link dept-nav__link--muted" href="<?= e(app_url('/#entrega')) ?>">Frete e entrega</a>
      <?php if ($isLogged): ?>
        <form method="POST" action="<?= e(app_url('/sair')) ?>" class="dept-nav__logout">
          <?= csrf_field() ?>
          <button type="submit" class="dept-nav__link dept-nav__link--muted">Sair</button>
        </form>
      <?php else: ?>
        <a class="dept-nav__link dept-nav__link--muted dept-nav__mobile-only" href="<?= e(app_url('/cadastro')) ?>">Criar conta</a>
      <?php endif; ?>
    </div>
  </nav>
</header>
