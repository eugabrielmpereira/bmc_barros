// BMC Barros — checkout: cotação de frete ao vivo e forma de pagamento.
// O valor exibido é informativo; o servidor recalcula o frete ao gravar o pedido.

(function () {
  const form = document.getElementById('checkout-form');
  if (!form) {
    return;
  }

  const quoteUrl = form.dataset.shippingQuoteUrl;
  const result = form.querySelector('[data-shipping-result]');
  const title = form.querySelector('[data-shipping-title]');
  const message = form.querySelector('[data-shipping-message]');
  const summaryShipping = document.querySelector('[data-summary-shipping]');
  const summaryTotal = document.querySelector('[data-summary-total]');
  const summaryTotalMobile = document.querySelector('[data-summary-total-mobile]');
  const freeHint = document.querySelector('[data-free-hint]');
  const installmentsField = form.querySelector('[data-installments-field]');
  const installmentsSelect = form.querySelector('[data-installments-select]');
  const cardNote = form.querySelector('[data-card-note]');
  const cepStatus = form.querySelector('[data-cep-status]');

  const addressFields = ['shipping_postal_code', 'shipping_neighborhood', 'shipping_city', 'shipping_state'];
  let timer = null;
  let controller = null;

  function deliveryMethod() {
    const checked = form.querySelector('[data-delivery-method-input]:checked');
    return checked ? checked.value : 'delivery';
  }

  function paymentMethod() {
    const checked = form.querySelector('[data-payment-method-input]:checked');
    return checked ? checked.value : 'pix';
  }

  function setState(state, heading, text) {
    if (!result) {
      return;
    }
    result.classList.remove('shipping-result--ok', 'shipping-result--error', 'shipping-result--pending');
    result.classList.add('shipping-result--' + state);
    if (title) {
      title.textContent = heading;
    }
    if (message) {
      message.textContent = text;
    }
  }

  function renderInstallments(options) {
    if (!installmentsSelect || !Array.isArray(options) || options.length === 0) {
      return;
    }
    const previous = Number(installmentsSelect.value || 1);
    installmentsSelect.innerHTML = '';
    options.forEach((option) => {
      const el = document.createElement('option');
      el.value = String(option.count);
      el.textContent = option.label;
      if (option.count === Math.min(previous, options.length)) {
        el.selected = true;
      }
      installmentsSelect.appendChild(el);
    });
  }

  async function requestQuote() {
    if (!quoteUrl) {
      return;
    }

    const isPickup = deliveryMethod() === 'pickup';
    if (!isPickup) {
      const city = form.querySelector('[name="shipping_city"]')?.value.trim();
      const state = form.querySelector('[name="shipping_state"]')?.value.trim();
      if (!city || !state) {
        setState('pending', 'Frete: informe o endereço', 'Preencha CEP, bairro e cidade para calcular.');
        if (summaryShipping) {
          summaryShipping.textContent = 'Informe o endereço';
        }
        return;
      }
      setState('pending', 'Calculando frete…', '');
    }

    const body = new FormData();
    body.append('csrf_token', form.querySelector('[name="csrf_token"]')?.value || '');
    body.append('delivery_method', deliveryMethod());
    addressFields.forEach((name) => {
      const field = form.querySelector('[name="' + name + '"]');
      if (field) {
        body.append(name, field.value);
      }
    });

    if (controller) {
      controller.abort();
    }
    controller = new AbortController();

    try {
      const response = await fetch(quoteUrl, {
        method: 'POST',
        body,
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        credentials: 'same-origin',
        signal: controller.signal,
      });
      const payload = await response.json();
      if (!payload || !payload.quote) {
        throw new Error('invalid');
      }

      const quote = payload.quote;
      if (isPickup) {
        setState('ok', 'Retirada na loja', 'Sem custo. Avisamos pelo WhatsApp quando estiver pronto.');
      } else if (quote.available) {
        const extra = quote.lead_time ? ' ' + quote.lead_time + '.' : '';
        setState('ok', 'Frete: ' + payload.shipping_label, quote.message + extra);
      } else {
        setState('error', 'Entrega indisponível', quote.message);
      }

      if (summaryShipping) {
        summaryShipping.textContent = quote.available || isPickup ? payload.shipping_label : '—';
      }
      if (summaryTotal) {
        summaryTotal.textContent = payload.total_label;
      }
      if (summaryTotalMobile) {
        summaryTotalMobile.textContent = payload.total_label;
      }
      if (freeHint) {
        const missing = Number(quote.missing_for_free_shipping || 0);
        freeHint.hidden = !(missing > 0 && !isPickup);
        if (missing > 0) {
          freeHint.textContent = 'Faltam ' + missing.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }) + ' para frete grátis.';
        }
      }
      renderInstallments(payload.installment_options);
    } catch (error) {
      if (error && error.name === 'AbortError') {
        return;
      }
      setState('pending', 'Frete calculado ao confirmar', 'Não foi possível calcular agora. O valor aparece no resumo do pedido.');
    }
  }

  function scheduleQuote(delay) {
    window.clearTimeout(timer);
    timer = window.setTimeout(requestQuote, delay);
  }

  function syncPayment() {
    const isCard = paymentMethod() === 'credit_card';
    if (installmentsField) {
      installmentsField.hidden = !isCard;
    }
    if (cardNote) {
      cardNote.hidden = !isCard;
    }
  }

  form.querySelectorAll('[data-delivery-method-input]').forEach((input) => {
    input.addEventListener('change', () => scheduleQuote(0));
  });

  addressFields.forEach((name) => {
    const field = form.querySelector('[name="' + name + '"]');
    if (field) {
      field.addEventListener('change', () => scheduleQuote(150));
      field.addEventListener('input', () => scheduleQuote(700));
    }
  });

  // O preenchimento automático por CEP altera os campos sem disparar eventos.
  if (cepStatus && 'MutationObserver' in window) {
    new MutationObserver(() => scheduleQuote(400)).observe(cepStatus, { childList: true, characterData: true, subtree: true, attributes: true });
  }

  form.querySelectorAll('[data-payment-method-input]').forEach((input) => input.addEventListener('change', syncPayment));

  form.addEventListener('submit', () => {
    const button = form.querySelector('[data-checkout-submit]');
    if (button && form.checkValidity()) {
      button.disabled = true;
      button.textContent = 'Enviando pedido…';
    }
  });

  syncPayment();
  if (deliveryMethod() === 'pickup' || form.querySelector('[name="shipping_city"]')?.value.trim()) {
    scheduleQuote(0);
  }
})();
