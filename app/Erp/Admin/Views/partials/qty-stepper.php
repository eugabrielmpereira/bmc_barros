<?php
/** @var string $name */
/** @var int $value */
/** @var int $min */
/** @var int|null $max */
/** @var bool $autoSubmit */
/** @var string $inputId */
$name = $name ?? 'quantity';
$value = (int) ($value ?? 1);
$min = (int) ($min ?? 1);
$max = isset($max) ? (int) $max : null;
$autoSubmit = (bool) ($autoSubmit ?? false);
$inputId = $inputId ?? ('qty-' . bin2hex(random_bytes(4)));
?>
<div class="qty-stepper" data-qty-stepper<?= $autoSubmit ? ' data-qty-auto-submit' : '' ?>>
    <button type="button" class="qty-stepper__btn" data-qty-stepper-delta="-1" aria-label="Diminuir quantidade">−</button>
    <input
        class="qty-stepper__input"
        type="number"
        name="<?= e($name) ?>"
        id="<?= e($inputId) ?>"
        value="<?= $value ?>"
        min="<?= $min ?>"
        <?php if ($max !== null): ?>max="<?= $max ?>"<?php endif; ?>
        step="1"
        inputmode="numeric"
        data-qty-stepper-input
    />
    <button type="button" class="qty-stepper__btn" data-qty-stepper-delta="1" aria-label="Aumentar quantidade">+</button>
</div>
