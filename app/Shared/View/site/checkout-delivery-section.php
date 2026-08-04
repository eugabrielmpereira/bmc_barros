<?php

use Erp\Fulfillment\Support\DeliveryMethod;
use Erp\Fulfillment\Support\DeliveryMethodLabel;

/** @var array<string, mixed> $data */
/** @var array<string, string> $fieldErrors */

$data = $data ?? [];
$fieldErrors = $fieldErrors ?? [];

$deliveryMethod = DeliveryMethod::parse((string) ($data['delivery_method'] ?? DeliveryMethod::DELIVERY));

?>
<h2>Forma de recebimento</h2>
<fieldset class="commerce-form-row commerce-delivery-method" data-delivery-method-fieldset>
  <legend class="commerce-residence-type__legend">Como deseja receber? *</legend>
  <div class="commerce-residence-type__options">
    <label class="commerce-residence-type__option">
      <input
        type="radio"
        name="delivery_method"
        value="<?= e(DeliveryMethod::DELIVERY) ?>"
        <?= $deliveryMethod === DeliveryMethod::DELIVERY ? 'checked' : '' ?>
        required
        data-delivery-method-input
      />
      <span><?= e(DeliveryMethodLabel::resolve(DeliveryMethod::DELIVERY)) ?></span>
    </label>
    <label class="commerce-residence-type__option">
      <input
        type="radio"
        name="delivery_method"
        value="<?= e(DeliveryMethod::PICKUP) ?>"
        <?= $deliveryMethod === DeliveryMethod::PICKUP ? 'checked' : '' ?>
        data-delivery-method-input
      />
      <span><?= e(DeliveryMethodLabel::resolve(DeliveryMethod::PICKUP)) ?></span>
    </label>
  </div>
  <?= commerce_checkout_field_error_html($fieldErrors, 'delivery_method') ?>
</fieldset>

<div data-checkout-address-section>
  <?php require SHARED_PATH . '/View/site/checkout-address-fields.php'; ?>
</div>
