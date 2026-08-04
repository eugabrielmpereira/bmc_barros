/**
 * Commerce público: badge, toast, add AJAX e mini-carrinho (drawer).
 */
(function () {
  const config = readCommerceConfig();
  if (!config) {
    return;
  }

  const baseUrl = config.baseUrl;
  const csrfToken = config.csrfToken;

  function readCommerceConfig() {
    const el = document.getElementById('commerce-config');
    if (!el) {
      return null;
    }

    const baseUrl = (el.dataset.baseUrl || '').trim();
    if (baseUrl === '') {
      return null;
    }

    return {
      baseUrl,
      csrfToken: (el.dataset.csrfToken || '').trim(),
    };
  }

  const badge = document.querySelector('[data-cart-badge]');
  const toastRoot = ensureToastRoot();
  const drawerRoot = document.getElementById('commerce-drawer-root');
  const drawerBody = drawerRoot?.querySelector('[data-drawer-body]');
  const drawerFooter = drawerRoot?.querySelector('[data-drawer-footer]');
  const drawerCount = drawerRoot?.querySelector('[data-drawer-count]');
  const drawerSubtotal = drawerRoot?.querySelector('[data-drawer-subtotal]');
  let drawerOpen = false;
  let lastFocus = null;
  const buyNowModal = document.getElementById('commerce-buy-now-modal');
  let pendingBuyNowForm = null;
  let pendingBuyNowSubmitter = null;

  document.querySelectorAll('form[data-commerce-add]').forEach(bindAddForm);
  document.querySelectorAll('form[data-commerce-product-form]').forEach(bindProductForm);
  document.querySelectorAll('[data-qty-control]').forEach(bindQtyControl);
  document.querySelectorAll('[data-cart-drawer-trigger]').forEach(bindDrawerTrigger);
  bindBuyNowModal();
  bindCartPageAutoUpdate();

  if (drawerRoot) {
    drawerRoot.querySelectorAll('[data-drawer-close]').forEach((el) => {
      el.addEventListener('click', closeDrawer);
    });
    document.addEventListener('keydown', (event) => {
      if (drawerOpen && event.key === 'Escape') {
        closeDrawer();
      }
    });
  }

  refreshBadge();
  initCheckoutFlashErrors();

  function ensureToastRoot() {
    let root = document.getElementById('commerce-toast-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'commerce-toast-root';
      root.className = 'commerce-toast-root';
      root.setAttribute('aria-live', 'polite');
      root.setAttribute('aria-atomic', 'true');
      document.body.appendChild(root);
    }
    return root;
  }

  function bindCartPageAutoUpdate() {
    const cartForm = document.querySelector('form[data-commerce-cart-update]');
    if (!cartForm) {
      return;
    }

    cartForm.querySelectorAll('.commerce-cart-line__qty [data-qty-input]').forEach((input) => {
      input.addEventListener('change', () => {
        cartForm.submit();
      });
    });

    cartForm.querySelectorAll('.commerce-cart-line__remove').forEach((button) => {
      button.addEventListener('click', (event) => {
        if (!window.confirm('Remover este item do carrinho?')) {
          event.preventDefault();
        }
      });
    });
  }

  function bindDrawerTrigger(link) {
    link.addEventListener('click', async (event) => {
      const count = currentBadgeCount();
      if (count <= 0) {
        return;
      }
      event.preventDefault();
      await openDrawer();
    });
  }

  function currentBadgeCount() {
    if (!badge || badge.classList.contains('hidden')) {
      return 0;
    }
    const raw = badge.textContent?.trim() || '0';
    if (raw.endsWith('+')) {
      return 100;
    }
    return Math.max(0, parseInt(raw, 10) || 0);
  }

  function bindAddForm(form) {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      submitCartForm(form, null, { buyNow: false });
    });
  }

  function bindProductForm(form) {
    form.addEventListener('submit', (event) => {
      event.preventDefault();
      const submitter = event.submitter || null;
      const buyNow = submitter?.getAttribute('name') === 'buy_now'
        || submitter?.hasAttribute('data-commerce-buy-now');
      submitCartForm(form, submitter, { buyNow: Boolean(buyNow) });
    });
  }

  function bindQtyControl(root) {
    const input = root.querySelector('[data-qty-input]');
    if (!input) {
      return;
    }

    root.querySelectorAll('[data-qty-delta]').forEach((btn) => {
      btn.addEventListener('click', () => {
        const delta = parseInt(btn.getAttribute('data-qty-delta') || '0', 10);
        const current = parseInt(input.value || '1', 10) || 1;
        const max = parseInt(input.getAttribute('data-stock-max') || input.getAttribute('max') || '999', 10) || 999;
        const next = Math.max(1, Math.min(max, current + delta));
        input.value = String(next);
      });
    });

    input.addEventListener('change', () => {
      const max = parseInt(input.getAttribute('data-stock-max') || input.getAttribute('max') || '999', 10) || 999;
      const next = Math.max(1, Math.min(max, parseInt(input.value || '1', 10) || 1));
      input.value = String(next);
    });
  }

  function bindBuyNowModal() {
    if (!buyNowModal) {
      return;
    }

    buyNowModal.querySelectorAll('[data-buy-now-close]').forEach((el) => {
      el.addEventListener('click', closeBuyNowModal);
    });

    buyNowModal.querySelector('[data-buy-now-replace]')?.addEventListener('click', () => {
      if (pendingBuyNowForm) {
        submitCartForm(pendingBuyNowForm, pendingBuyNowSubmitter, {
          buyNow: true,
          replaceCart: true,
        });
      }
      closeBuyNowModal();
    });

    buyNowModal.querySelector('[data-buy-now-keep]')?.addEventListener('click', () => {
      if (pendingBuyNowForm) {
        submitCartForm(pendingBuyNowForm, pendingBuyNowSubmitter, {
          buyNow: true,
          buyNowKeep: true,
        });
      }
      closeBuyNowModal();
    });

    document.addEventListener('keydown', (event) => {
      if (buyNowModal.hidden === false && event.key === 'Escape') {
        closeBuyNowModal();
      }
    });
  }

  function openBuyNowModal(form, submitter) {
    if (!buyNowModal) {
      submitCartForm(form, submitter, { buyNow: true, replaceCart: true });
      return;
    }

    pendingBuyNowForm = form;
    pendingBuyNowSubmitter = submitter;
    buyNowModal.hidden = false;
    buyNowModal.setAttribute('aria-hidden', 'false');
    buyNowModal.classList.add('commerce-buy-now-modal--open');
    document.body.classList.add('commerce-buy-now-modal-open');
    buyNowModal.querySelector('[data-buy-now-replace]')?.focus();
  }

  function closeBuyNowModal() {
    if (!buyNowModal) {
      return;
    }

    buyNowModal.hidden = true;
    buyNowModal.setAttribute('aria-hidden', 'true');
    buyNowModal.classList.remove('commerce-buy-now-modal--open');
    document.body.classList.remove('commerce-buy-now-modal-open');
    pendingBuyNowForm = null;
    pendingBuyNowSubmitter = null;
  }

  async function submitCartForm(form, submitter, options) {
    const buyNow = Boolean(options?.buyNow);
    const replaceCart = Boolean(options?.replaceCart);
    const buyNowKeep = Boolean(options?.buyNowKeep);
    const buttons = form.querySelectorAll('[type="submit"]');
    buttons.forEach((btn) => {
      btn.disabled = true;
    });

    try {
      const body = buildCartFormBody(form, { buyNow, replaceCart, buyNowKeep });

      const response = await fetch(`${baseUrl}/carrinho/adicionar`, {
        method: 'POST',
        body,
        headers: {
          Accept: 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
      });

      const data = await response.json().catch(() => ({}));
      const message = typeof data.message === 'string'
        ? data.message
        : 'Não foi possível adicionar ao carrinho.';

      if (data.needs_confirm && buyNow) {
        openBuyNowModal(form, submitter);
        return;
      }

      if (response.ok && data.success) {
        updateBadge(data.item_count);
        if (data.adjusted) {
          const qtyInput = form.querySelector('[data-qty-input], [name="quantity"]');
          if (qtyInput && typeof data.available_stock === 'number') {
            qtyInput.value = String(data.available_stock);
            qtyInput.setAttribute('data-stock-max', String(data.available_stock));
            qtyInput.setAttribute('max', String(data.available_stock));
          }
          showToast(message, 'neutral');
        }
        if (typeof data.redirect_url === 'string' && data.redirect_url !== '') {
          window.location.href = data.redirect_url;
          return;
        }
        if (!buyNow) {
          await openDrawer(data);
        } else if (!data.adjusted) {
          showToast(message, 'success');
        }
      } else {
        showToast(message, data.out_of_stock ? 'error' : 'error');
      }
    } catch {
      showToast('Falha de conexão. Tente novamente.', 'error');
    } finally {
      buttons.forEach((btn) => {
        btn.disabled = false;
      });
    }
  }

  function buildCartFormBody(form, options) {
    const body = new FormData(form);
    if (!body.has('csrf_token') && csrfToken) {
      body.append('csrf_token', csrfToken);
    }

    if (options?.buyNow) {
      body.append('buy_now', '1');
    }
    if (options?.replaceCart) {
      body.append('replace_cart', '1');
    }
    if (options?.buyNowKeep) {
      body.append('buy_now_mode', 'keep');
    }

    return body;
  }

  async function fetchCart() {
    const response = await fetch(`${baseUrl}/carrinho/resumo`, {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!response.ok) {
      throw new Error('cart_fetch_failed');
    }
    return response.json();
  }

  async function openDrawer(prefetchedData = null) {
    if (!drawerRoot || !drawerBody) {
      window.location.href = `${baseUrl}/carrinho`;
      return;
    }

    if (!drawerOpen) {
      lastFocus = document.activeElement;
      drawerRoot.hidden = false;
      drawerRoot.setAttribute('aria-hidden', 'false');
      drawerRoot.classList.add('commerce-drawer-root--open');
      document.body.classList.add('commerce-drawer-open');
      drawerOpen = true;
      drawerRoot.querySelector('.commerce-drawer__close')?.focus();
    }

    if (prefetchedData && Array.isArray(prefetchedData.items)) {
      renderDrawer(prefetchedData);
      updateBadge(prefetchedData.item_count);
      return;
    }

    drawerBody.innerHTML = '<p class="commerce-drawer__loading">Carregando…</p>';
    if (drawerFooter) {
      drawerFooter.hidden = true;
    }

    try {
      const data = await fetchCart();
      renderDrawer(data);
      updateBadge(data.item_count);
    } catch {
      drawerBody.innerHTML = '<p class="commerce-drawer__error">Não foi possível carregar o carrinho.</p>';
    }
  }

  function closeDrawer() {
    if (!drawerRoot) {
      return;
    }
    drawerRoot.hidden = true;
    drawerRoot.setAttribute('aria-hidden', 'true');
    drawerRoot.classList.remove('commerce-drawer-root--open');
    document.body.classList.remove('commerce-drawer-open');
    drawerOpen = false;
    if (lastFocus && typeof lastFocus.focus === 'function') {
      lastFocus.focus();
    }
  }

  function renderDrawer(data) {
    if (!drawerBody) {
      return;
    }

    const items = Array.isArray(data.items) ? data.items : [];
    const itemCount = parseInt(String(data.item_count ?? 0), 10) || 0;
    const productCount = parseInt(String(data.product_count ?? items.length), 10) || 0;

    if (items.length === 0 || itemCount <= 0) {
      drawerBody.innerHTML = `
        <div class="commerce-drawer__empty">
          <p>Seu carrinho está vazio.</p>
          <a class="commerce-drawer__cta commerce-drawer__cta--primary" href="${baseUrl}/produtos">Ver produtos</a>
        </div>`;
      if (drawerFooter) {
        drawerFooter.hidden = true;
      }
      updateBadge(0);
      return;
    }

    const lines = items.map((item) => {
      const productId = parseInt(String(item.product_id ?? 0), 10);
      const qty = parseInt(String(item.quantity ?? 1), 10) || 1;
      const name = escapeHtml(String(item.name ?? 'Produto'));
      const totalLabel = escapeHtml(String(item.total_label ?? ''));
      const productUrl = escapeHtml(String(item.product_url ?? '#'));
      const thumb = item.image_url
        ? `<img class="commerce-drawer-item__thumb" src="${escapeHtml(String(item.image_url))}" alt="" />`
        : '<div class="commerce-drawer-item__thumb commerce-drawer-item__thumb--empty" aria-hidden="true">📦</div>';

      return `
        <article class="commerce-drawer-item" data-product-id="${productId}">
          ${thumb}
          <div class="commerce-drawer-item__info">
            <a class="commerce-drawer-item__name" href="${productUrl}">${name}</a>
            <p class="commerce-drawer-item__total">${totalLabel}</p>
          </div>
          <div class="commerce-drawer-item__qty">
            <button type="button" class="commerce-drawer-item__qty-btn" data-qty-delta="-1" aria-label="Diminuir quantidade">−</button>
            <input type="number" min="0" max="999" value="${qty}" aria-label="Quantidade" data-qty-input />
            <button type="button" class="commerce-drawer-item__qty-btn" data-qty-delta="1" aria-label="Aumentar quantidade">+</button>
          </div>
          <button type="button" class="commerce-drawer-item__remove" data-remove-item aria-label="Remover item">✕</button>
        </article>`;
    }).join('');

    drawerBody.innerHTML = `<div class="commerce-drawer__items">${lines}</div>`;
    bindDrawerItemEvents();

    if (drawerFooter) {
      drawerFooter.hidden = false;
    }
    if (drawerCount) {
      drawerCount.textContent = `${itemCount} item(ns) · ${productCount} produto(s)`;
    }
    if (drawerSubtotal) {
      drawerSubtotal.textContent = String(data.subtotal_label ?? '');
    }
  }

  function bindDrawerItemEvents() {
    if (!drawerBody) {
      return;
    }

    drawerBody.querySelectorAll('.commerce-drawer-item').forEach((row) => {
      const productId = parseInt(row.getAttribute('data-product-id') || '0', 10);
      const input = row.querySelector('[data-qty-input]');

      row.querySelectorAll('[data-qty-delta]').forEach((btn) => {
        btn.addEventListener('click', async () => {
          const delta = parseInt(btn.getAttribute('data-qty-delta') || '0', 10);
          const current = parseInt(input?.value || '1', 10) || 1;
          const next = Math.max(0, Math.min(999, current + delta));
          if (input) {
            input.value = String(next);
          }
          await applyQuantity(productId, next);
        });
      });

      input?.addEventListener('change', async () => {
        const next = Math.max(0, Math.min(999, parseInt(input.value || '0', 10) || 0));
        input.value = String(next);
        await applyQuantity(productId, next);
      });

      row.querySelector('[data-remove-item]')?.addEventListener('click', async () => {
        await removeItem(productId);
      });
    });
  }

  async function applyQuantity(productId, quantity) {
    if (productId <= 0) {
      return;
    }

    try {
      const data = quantity <= 0
        ? await removeItem(productId, false)
        : await postCartForm(`${baseUrl}/carrinho/atualizar`, (body) => {
            body.append(`quantities[${productId}]`, String(quantity));
          });

      if (data) {
        renderDrawer(data);
        updateBadge(data.item_count);
        if ((data.item_count ?? 0) <= 0) {
          closeDrawer();
        }
      }
    } catch {
      showToast('Não foi possível atualizar o carrinho.', 'error');
    }
  }

  async function removeItem(productId, showFeedback = true) {
    if (productId <= 0) {
      return null;
    }

    try {
      const data = await postCartForm(`${baseUrl}/carrinho/remover`, (body) => {
        body.append('product_id', String(productId));
      });

      if (data) {
        renderDrawer(data);
        updateBadge(data.item_count);
        if (showFeedback && typeof data.message === 'string') {
          showToast(data.message, 'neutral');
        }
        if ((data.item_count ?? 0) <= 0) {
          closeDrawer();
        }
      }
      return data;
    } catch {
      showToast('Não foi possível remover o item.', 'error');
      return null;
    }
  }

  async function postCartForm(url, appendFields) {
    const body = new FormData();
    if (csrfToken) {
      body.append('csrf_token', csrfToken);
    }
    appendFields(body);

    const response = await fetch(url, {
      method: 'POST',
      body,
      headers: {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      credentials: 'same-origin',
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok) {
      const message = typeof data.message === 'string' ? data.message : 'Operação não concluída.';
      showToast(message, 'error');
      return null;
    }
    return data;
  }

  async function refreshBadge() {
    if (!badge) {
      return;
    }

    try {
      const data = await fetchCart();
      updateBadge(data.item_count);
    } catch {
      // Mantém contagem renderizada no servidor.
    }
  }

  function updateBadge(count) {
    if (!badge) {
      return;
    }

    const total = Math.max(0, parseInt(String(count ?? 0), 10) || 0);
    badge.textContent = total > 99 ? '99+' : String(total);
    badge.classList.toggle('hidden', total <= 0);
    badge.closest('.cart-nav-link')?.setAttribute(
      'aria-label',
      total > 0 ? `Carrinho, ${total} itens` : 'Carrinho'
    );
    badge.classList.add('cart-nav-badge--pulse');
    window.setTimeout(() => badge.classList.remove('cart-nav-badge--pulse'), 400);
  }

  function initCheckoutFlashErrors() {
    const el = document.getElementById('checkout-flash-errors');
    if (!el) {
      return;
    }

    let messages = [];
    try {
      messages = JSON.parse(el.dataset.messages || '[]');
    } catch {
      return;
    }

    if (!Array.isArray(messages) || messages.length === 0) {
      return;
    }

    toastRoot.classList.add('commerce-toast-root--checkout');

    messages.forEach((message, index) => {
      window.setTimeout(() => {
        showToast(String(message), 'error', 4000);
      }, index * 150);
    });

    window.setTimeout(() => {
      toastRoot.classList.remove('commerce-toast-root--checkout');
    }, 4000 + messages.length * 150);

    const firstInvalid = document.querySelector('#checkout-form .is-invalid');
    if (firstInvalid instanceof HTMLElement) {
      firstInvalid.focus({ preventScroll: true });
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
    } else {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  }

  function showToast(message, type, durationMs) {
    const toast = document.createElement('div');
    const variant = type === 'success' ? 'success' : type === 'neutral' ? 'neutral' : 'error';
    toast.className = `commerce-toast commerce-toast--${variant}`;
    toast.setAttribute('role', variant === 'error' ? 'alert' : 'status');

    const text = document.createElement('p');
    text.className = 'commerce-toast__message';
    text.textContent = message;
    toast.appendChild(text);

    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'commerce-toast__close';
    closeBtn.setAttribute('aria-label', 'Fechar aviso');
    closeBtn.textContent = '×';
    closeBtn.addEventListener('click', () => removeToast(toast));
    toast.appendChild(closeBtn);

    toastRoot.appendChild(toast);

    const defaultMs = type === 'error' ? 5000 : 4000;
    const timeout = window.setTimeout(() => removeToast(toast), durationMs ?? defaultMs);
    toast.dataset.timeoutId = String(timeout);
  }

  function removeToast(toast) {
    const timeoutId = parseInt(toast.dataset.timeoutId || '0', 10);
    if (timeoutId) {
      window.clearTimeout(timeoutId);
    }
    toast.classList.add('commerce-toast--leaving');
    window.setTimeout(() => toast.remove(), 200);
  }

  function escapeHtml(value) {
    return value
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }
})();
