<div id="commerce-drawer-root" class="commerce-drawer-root" hidden aria-hidden="true">
  <div class="commerce-drawer-overlay" data-drawer-close tabindex="-1"></div>
  <aside
    class="commerce-drawer"
    role="dialog"
    aria-modal="true"
    aria-labelledby="commerce-drawer-title"
  >
    <header class="commerce-drawer__header">
      <h2 id="commerce-drawer-title">Seu carrinho</h2>
      <button type="button" class="commerce-drawer__close" data-drawer-close aria-label="Fechar carrinho">×</button>
    </header>
    <div class="commerce-drawer__body" data-drawer-body>
      <p class="commerce-drawer__loading">Carregando…</p>
    </div>
    <footer class="commerce-drawer__footer" data-drawer-footer hidden>
      <p class="commerce-drawer__count" data-drawer-count></p>
      <div class="commerce-drawer__subtotal">
        <span class="commerce-drawer__subtotal-label">Subtotal</span>
        <strong class="commerce-drawer__subtotal-value" data-drawer-subtotal></strong>
      </div>
      <div class="commerce-drawer__actions">
        <a class="commerce-drawer__cta commerce-drawer__cta--primary" data-drawer-checkout href="<?= e(app_url('/checkout')) ?>">Finalizar pedido</a>
        <a class="commerce-drawer__cta commerce-drawer__cta--secondary" data-drawer-full-cart href="<?= e(app_url('/carrinho')) ?>">Ver carrinho completo</a>
      </div>
      <p class="commerce-drawer__note">Valores e disponibilidade confirmados pela loja.</p>
    </footer>
  </aside>
</div>
