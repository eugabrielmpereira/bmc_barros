/**
 * Validação client-side de CPF/CNPJ no checkout (feedback imediato).
 */
(function () {
  const form = document.getElementById('checkout-form');
  if (!form) {
    return;
  }

  const documentInput = form.querySelector('[name="document"]');
  if (!documentInput) {
    return;
  }

  function onlyDigits(value) {
    return String(value || '').replace(/\D/g, '');
  }

  function isRepeatedDigits(digits) {
    return /^(\d)\1+$/.test(digits);
  }

  function isValidCpf(cpf) {
    if (cpf.length !== 11 || isRepeatedDigits(cpf)) {
      return false;
    }

    for (let t = 9; t < 11; t++) {
      let sum = 0;
      for (let i = 0; i < t; i++) {
        sum += parseInt(cpf[i], 10) * (t + 1 - i);
      }
      const digit = (10 * sum) % 11 % 10;
      if (parseInt(cpf[t], 10) !== digit) {
        return false;
      }
    }

    return true;
  }

  function isValidCnpj(cnpj) {
    if (cnpj.length !== 14 || isRepeatedDigits(cnpj)) {
      return false;
    }

    const weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    const weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    let sum = 0;
    for (let i = 0; i < 12; i++) {
      sum += parseInt(cnpj[i], 10) * weights1[i];
    }
    let digit1 = sum % 11 < 2 ? 0 : 11 - (sum % 11);
    if (parseInt(cnpj[12], 10) !== digit1) {
      return false;
    }

    sum = 0;
    for (let i = 0; i < 13; i++) {
      sum += parseInt(cnpj[i], 10) * weights2[i];
    }
    const digit2 = sum % 11 < 2 ? 0 : 11 - (sum % 11);

    return parseInt(cnpj[13], 10) === digit2;
  }

  function isValidDocument(value) {
    const digits = onlyDigits(value);
    if (digits.length === 11) {
      return isValidCpf(digits);
    }
    if (digits.length === 14) {
      return isValidCnpj(digits);
    }
    return false;
  }

  function getOrCreateErrorEl() {
    const label = documentInput.closest('label');
    if (!label) {
      return null;
    }

    let errorEl = label.querySelector('.commerce-field-error');
    if (!errorEl) {
      errorEl = document.createElement('span');
      errorEl.className = 'commerce-field-error';
      errorEl.setAttribute('role', 'alert');
      label.appendChild(errorEl);
    }

    return errorEl;
  }

  function setFieldState(message) {
    documentInput.classList.toggle('is-invalid', message !== '');
    const errorEl = getOrCreateErrorEl();
    if (errorEl) {
      errorEl.textContent = message;
      errorEl.hidden = message === '';
    }
  }

  function validateDocument() {
    const digits = onlyDigits(documentInput.value);
    if (digits.length === 0) {
      setFieldState('');
      return true;
    }
    if (digits.length !== 11 && digits.length !== 14) {
      setFieldState('CPF/CNPJ incompleto.');
      return false;
    }
    if (!isValidDocument(documentInput.value)) {
      setFieldState('CPF/CNPJ inválido. Verifique os dígitos verificadores.');
      return false;
    }
    setFieldState('');
    return true;
  }

  documentInput.addEventListener('blur', validateDocument);
  documentInput.addEventListener('input', function () {
    const digits = onlyDigits(documentInput.value);
    if (digits.length < 11) {
      setFieldState('');
    }
  });

  form.addEventListener('submit', function (event) {
    if (!validateDocument()) {
      event.preventDefault();
      documentInput.focus();
    }
  });
})();
