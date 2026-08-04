<?php

declare(strict_types=1);

$config = client_config();
$clientLegalName = (string) ($clientLegalName ?? ($config['legalName'] ?? $config['name'] ?? 'Cliente'));
$cnpj = trim((string) ($config['cnpj'] ?? ''));
$address = trim((string) ($config['address'] ?? ''));
$privacyUrl = client_legal_page_url('privacy');
$termsUrl = client_legal_page_url('terms');
?>
<div class="commerce-footer-minimal">
  <p class="commerce-footer-minimal__identity">
    <strong><?= e($clientLegalName) ?></strong>
    <?php if ($cnpj !== ''): ?>
      — CNPJ <?= e($cnpj) ?>
    <?php endif; ?>
    <?php if ($address !== ''): ?>
      — <?= e($address) ?>
    <?php endif; ?>
  </p>
  <p class="commerce-footer-minimal__note">Pedido sujeito à confirmação comercial.</p>
  <?php if ($privacyUrl !== null || $termsUrl !== null): ?>
    <nav class="commerce-footer-minimal__legal" aria-label="Documentos legais">
      <?php if ($privacyUrl !== null): ?>
        <a href="<?= e($privacyUrl) ?>">Política de Privacidade</a>
      <?php endif; ?>
      <?php if ($termsUrl !== null): ?>
        <a href="<?= e($termsUrl) ?>">Termos de Uso</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</div>
