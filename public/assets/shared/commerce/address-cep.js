/**
 * Auto-preenchimento de endereço via consulta CEP (proxy same-origin + ViaCEP no servidor).
 */
(function () {
  const form = document.getElementById('checkout-form');
  if (!form) {
    return;
  }

  const configEl = document.getElementById('commerce-config');
  const baseUrl = (configEl && configEl.dataset.baseUrl) || '';
  const postalInput = form.querySelector('[data-cep-input]');
  const stateSelect = form.querySelector('[name="shipping_state"]');
  const streetInput = form.querySelector('[name="shipping_street"]');
  const neighborhoodInput = form.querySelector('[name="shipping_neighborhood"]');
  const cityInput = form.querySelector('[name="shipping_city"]');
  const statusEl = form.querySelector('[data-cep-status]');
  const cepValidatedInput = form.querySelector('[data-cep-validated]');

  if (!postalInput) {
    return;
  }

  let lookupAbort = null;

  function setCepValidated(validated) {
    if (cepValidatedInput) {
      cepValidatedInput.value = validated ? '1' : '0';
    }
  }

  function onlyDigits(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function formatCepMask(digits) {
    const d = onlyDigits(digits).slice(0, 8);
    if (d.length <= 5) {
      return d;
    }
    return d.slice(0, 5) + '-' + d.slice(5);
  }

  function caretAfterDigits(digitCount) {
    if (digitCount <= 5) {
      return digitCount;
    }
    return digitCount + 1;
  }

  function setStatus(message, type) {
    if (!statusEl) {
      return;
    }
    statusEl.textContent = message;
    statusEl.hidden = message === '';
    statusEl.classList.remove('commerce-cep-status--loading', 'commerce-cep-status--error');
    if (type) {
      statusEl.classList.add('commerce-cep-status--' + type);
    }
  }

  function fillAddress(data) {
    if (streetInput && data.logradouro) {
      streetInput.value = data.logradouro;
    }
    if (neighborhoodInput && data.bairro) {
      neighborhoodInput.value = data.bairro;
    }
    if (cityInput && data.localidade) {
      cityInput.value = data.localidade;
    }
    if (stateSelect && data.uf) {
      stateSelect.value = data.uf;
    }
  }

  async function lookupCep() {
    const digits = onlyDigits(postalInput.value);
    if (digits.length !== 8) {
      setStatus('', '');
      return;
    }

    if (lookupAbort) {
      lookupAbort.abort();
    }
    lookupAbort = new AbortController();

    setStatus('Buscando CEP…', 'loading');

    const lookupUrl =
      baseUrl + '/cep-consulta?cep=' + encodeURIComponent(digits);

    try {
      const response = await fetch(lookupUrl, {
        signal: lookupAbort.signal,
        headers: { Accept: 'application/json' },
      });

      const body = await response.json();

      if (!response.ok || !body.success) {
        setStatus(body.error || 'CEP inválido. Verifique os dígitos.', 'error');
        return;
      }

      fillAddress(body.data || {});
      setCepValidated(true);
      setStatus('Endereço preenchido. Confira número e complemento.', '');
      form.querySelector('[name="shipping_number"]')?.focus();
    } catch (err) {
      if (err.name === 'AbortError') {
        return;
      }
      setStatus('Não foi possível consultar o CEP. Preencha manualmente.', 'error');
    } finally {
      lookupAbort = null;
    }
  }

  postalInput.addEventListener('input', function () {
    const selectionStart = postalInput.selectionStart ?? 0;
    const digitsBeforeCaret = onlyDigits(
      postalInput.value.slice(0, selectionStart)
    ).length;

    const digits = onlyDigits(postalInput.value).slice(0, 8);
    const formatted = formatCepMask(digits);
    postalInput.value = formatted;

    const pos = Math.min(caretAfterDigits(digitsBeforeCaret), formatted.length);
    postalInput.setSelectionRange(pos, pos);

    if (digits.length < 8) {
      setStatus('', '');
      setCepValidated(false);
    }
  });

  postalInput.addEventListener('blur', lookupCep);

  if (onlyDigits(postalInput.value).length === 8) {
    const hasStreet = streetInput && streetInput.value.trim() !== '';
    if (!hasStreet) {
      lookupCep();
    }
  }

  const complementInput = form.querySelector('[data-complement-input]');
  const complementRequiredMark = form.querySelector('[data-complement-required-mark]');
  const complementHint = form.querySelector('[data-complement-hint]');
  const residenceInputs = form.querySelectorAll('[data-residence-type-input]');

  function syncResidenceTypeUi() {
    const selected = form.querySelector('[data-residence-type-input]:checked');
    const isBuilding = selected && selected.value === 'building';

    if (complementInput) {
      complementInput.required = Boolean(isBuilding);
      complementInput.placeholder = isBuilding
        ? 'Ex.: Apto 12, Bloco B, Sala 3'
        : 'Opcional para casa';
    }
    if (complementRequiredMark) {
      complementRequiredMark.hidden = !isBuilding;
    }
    if (complementHint) {
      complementHint.hidden = !isBuilding;
    }
  }

  residenceInputs.forEach((input) => {
    input.addEventListener('change', syncResidenceTypeUi);
  });
  syncResidenceTypeUi();
})();
