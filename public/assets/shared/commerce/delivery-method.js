/**
 * Alterna endereço de entrega conforme retirada vs entrega no checkout.
 */
(function () {
  const form = document.getElementById('checkout-form');
  if (!form) {
    return;
  }

  const addressSection = form.querySelector('[data-checkout-address-section]');
  if (!addressSection) {
    return;
  }

  const methodInputs = form.querySelectorAll('[data-delivery-method-input]');
  if (methodInputs.length === 0) {
    return;
  }

  function selectedMethod() {
    const checked = form.querySelector('[data-delivery-method-input]:checked');
    return checked ? checked.value : 'delivery';
  }

  function setFieldRequired(field, required) {
    if (!field) {
      return;
    }
    if (required) {
      field.setAttribute('required', 'required');
    } else {
      field.removeAttribute('required');
    }
  }

  function toggleAddressSection() {
    const isPickup = selectedMethod() === 'pickup';
    addressSection.hidden = isPickup;

    addressSection.querySelectorAll('input, select, textarea').forEach(function (field) {
      const name = field.getAttribute('name') || '';
      const skipOptional = name === 'address_reference' || name === 'shipping_complement';
      if (skipOptional) {
        setFieldRequired(field, false);
        return;
      }
      if (field.type === 'radio' && name === 'residence_type') {
        setFieldRequired(field, !isPickup);
        return;
      }
      setFieldRequired(field, !isPickup);
    });
  }

  methodInputs.forEach(function (input) {
    input.addEventListener('change', toggleAddressSection);
  });

  toggleAddressSection();
})();
