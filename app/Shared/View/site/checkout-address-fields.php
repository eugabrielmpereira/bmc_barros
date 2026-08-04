<?php

use Shared\Support\BrazilianAddress;

/** @var array<string, mixed> $data */
/** @var array<string, string> $fieldErrors */

$data = $data ?? [];
$fieldErrors = $fieldErrors ?? [];

$shippingState = strtoupper((string) ($data['shipping_state'] ?? ''));
$residenceType = (string) ($data['residence_type'] ?? '');
$postalDisplay = (string) ($data['shipping_postal_code'] ?? '');
if ($postalDisplay !== '' && strlen(BrazilianAddress::normalizePostalCode($postalDisplay)) === 8) {
    $postalDisplay = BrazilianAddress::formatPostalCode($postalDisplay);
}

?>
<input type="hidden" name="cep_validated" value="0" data-cep-validated />
<h2>Endereço de entrega</h2>

<div class="commerce-form-row commerce-form-row-2">
  <label>UF *
    <select
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_state')) ?>"
      name="shipping_state"
      required
      data-checkout-uf
    >
      <option value="">Selecione</option>
      <?php foreach (BrazilianAddress::STATES as $uf => $label): ?>
        <option value="<?= e($uf) ?>" <?= $shippingState === $uf ? 'selected' : '' ?>><?= e($uf) ?> — <?= e($label) ?></option>
      <?php endforeach; ?>
    </select>
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_state') ?>
  </label>

  <label>CEP *
    <input
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_postal_code')) ?>"
      type="text"
      name="shipping_postal_code"
      value="<?= e($postalDisplay) ?>"
      inputmode="numeric"
      autocomplete="postal-code"
      maxlength="9"
      placeholder="00000-000"
      required
      data-cep-input
    />
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_postal_code') ?>
    <span class="commerce-cep-status" data-cep-status hidden></span>
  </label>
</div>

<div class="commerce-form-row">
  <label>Logradouro *
    <input
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_street')) ?>"
      type="text"
      name="shipping_street"
      value="<?= e((string) ($data['shipping_street'] ?? '')) ?>"
      required
      autocomplete="street-address"
    />
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_street') ?>
  </label>
</div>

<fieldset class="commerce-form-row commerce-residence-type" data-residence-fieldset>
  <legend class="commerce-residence-type__legend">Tipo do imóvel *</legend>
  <div class="commerce-residence-type__options">
    <label class="commerce-residence-type__option">
      <input
        type="radio"
        name="residence_type"
        value="house"
        <?= $residenceType === 'house' ? 'checked' : '' ?>
        required
        data-residence-type-input
      />
      <span>Casa</span>
    </label>
    <label class="commerce-residence-type__option">
      <input
        type="radio"
        name="residence_type"
        value="building"
        <?= $residenceType === 'building' ? 'checked' : '' ?>
        data-residence-type-input
      />
      <span>Prédio / apartamento</span>
    </label>
  </div>
  <?= commerce_checkout_field_error_html($fieldErrors, 'residence_type') ?>
</fieldset>

<div class="commerce-form-row commerce-form-row-2">
  <label>Número *
    <input
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_number')) ?>"
      type="text"
      name="shipping_number"
      value="<?= e((string) ($data['shipping_number'] ?? '')) ?>"
      required
      autocomplete="off"
    />
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_number') ?>
  </label>

  <label data-complement-label class="commerce-complement-label">
    <span class="commerce-complement-label__text">Complemento<span data-complement-required-mark class="commerce-complement-label__required" hidden>*</span></span>
    <input
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_complement')) ?>"
      type="text"
      name="shipping_complement"
      value="<?= e((string) ($data['shipping_complement'] ?? '')) ?>"
      autocomplete="off"
      placeholder="Opcional para casa"
      data-complement-input
      minlength="3"
    />
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_complement') ?>
    <span class="commerce-complement-hint" data-complement-hint hidden>
      Obrigatório em prédio: informe apartamento, bloco ou sala (mín. 3 caracteres).
    </span>
  </label>
</div>

<div class="commerce-form-row commerce-form-row-2">
  <label>Bairro *
    <input
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_neighborhood')) ?>"
      type="text"
      name="shipping_neighborhood"
      value="<?= e((string) ($data['shipping_neighborhood'] ?? '')) ?>"
      required
    />
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_neighborhood') ?>
  </label>

  <label>Cidade *
    <input
      class="<?= e(commerce_checkout_input_class($fieldErrors, 'shipping_city')) ?>"
      type="text"
      name="shipping_city"
      value="<?= e((string) ($data['shipping_city'] ?? '')) ?>"
      required
      autocomplete="address-level2"
    />
    <?= commerce_checkout_field_error_html($fieldErrors, 'shipping_city') ?>
  </label>
</div>

<div class="commerce-form-row">
  <label>Ponto de referência
    <input
      class="form-control"
      type="text"
      name="address_reference"
      value="<?= e((string) ($data['address_reference'] ?? '')) ?>"
      placeholder="Ex.: portaria azul, casa dos fundos"
    />
  </label>
</div>
