<?php

$pixCharge = $pix_charge ?? null;
if (!is_array($pixCharge)) {
    return;
}

$pixPayload = trim((string) ($pixCharge['pix_copy_paste'] ?? ''));
$pixImage = trim((string) ($pixCharge['pix_qr_image_base64'] ?? ''));
$invoiceUrl = trim((string) ($pixCharge['invoice_url'] ?? ''));
$chargeStatus = strtoupper(trim((string) ($pixCharge['status'] ?? 'PENDING')));
$isPaid = \Erp\Finance\Services\PaymentChargeService::isPaidStatus($chargeStatus);

if ($pixPayload === '' && $pixImage === '' && $invoiceUrl === '') {
    return;
}
?>
<section class="commerce-pix-payment">
  <h2>Pagamento via Pix</h2>
  <?php if ($isPaid): ?>
    <p class="commerce-pix-payment__status commerce-pix-payment__status--paid">
      Pagamento Pix recebido. Nossa equipe confirma o pedido em seguida.
    </p>
  <?php else: ?>
    <p class="commerce-pix-payment__hint">
      Você pode pagar agora para agilizar a confirmação. Valores e estoque seguem sujeitos à validação da loja.
    </p>
  <?php endif; ?>

  <?php if ($pixImage !== '' && !$isPaid): ?>
    <div class="commerce-pix-payment__qr">
      <img
        src="data:image/png;base64,<?= e($pixImage) ?>"
        alt="QR Code Pix"
        width="220"
        height="220"
      />
    </div>
  <?php endif; ?>

  <?php if ($pixPayload !== '' && !$isPaid): ?>
    <label class="commerce-pix-payment__copy-label" for="pix-copy-paste">Pix copia e cola</label>
    <div class="commerce-pix-payment__copy-row">
      <textarea id="pix-copy-paste" class="form-control" rows="3" readonly><?= e($pixPayload) ?></textarea>
      <button type="button" class="btn secondary" data-copy-target="pix-copy-paste">Copiar</button>
    </div>
  <?php endif; ?>

  <?php if ($invoiceUrl !== '' && !$isPaid): ?>
    <p><a class="btn secondary" href="<?= e($invoiceUrl) ?>" target="_blank" rel="noreferrer">Abrir fatura Pix</a></p>
  <?php endif; ?>
</section>
<script src="<?= shared_commerce_asset('pix-copy.js') ?>" defer></script>
