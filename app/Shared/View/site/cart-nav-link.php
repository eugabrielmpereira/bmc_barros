<?php
$cartNavCount = isset($cartNavCount) ? max(0, (int) $cartNavCount) : 0;
$cartNavHidden = $cartNavCount <= 0 ? ' hidden' : '';
?>
<a class="cart-nav-link" href="<?= e(app_url('/carrinho')) ?>" data-cart-drawer-trigger aria-label="Carrinho<?= $cartNavCount > 0 ? ', ' . $cartNavCount . ' itens' : '' ?>">
  <span class="cart-nav-link__label">Carrinho</span>
  <span class="cart-nav-badge<?= $cartNavHidden ?>" data-cart-badge aria-live="polite"><?= $cartNavCount > 99 ? '99+' : (string) $cartNavCount ?></span>
</a>
