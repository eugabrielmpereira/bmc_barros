<?php

declare(strict_types=1);

/** @var array<string, mixed> $client */
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
$cnpj = trim((string) ($client['cnpj'] ?? ''));
$privacyUrl = client_legal_page_url('privacy');
$termsUrl = client_legal_page_url('terms');
?>
<footer>
  <?php if ($withLogo && !empty($client['assets']['logo'])): ?>
    <img src="<?= BASE_URL . e((string) $client['assets']['logo']) ?>" alt="Logomarca <?= e($clientName) ?>" />
  <?php endif; ?>
  <div>
    <strong><?= e($clientLegalName) ?></strong>
    <?php if ($cnpj !== ''): ?>
      <p>CNPJ: <?= e($cnpj) ?></p>
    <?php endif; ?>
    <p>Endereço: <?= e((string) ($client['address'] ?? '')) ?></p>
    <p>WhatsApp e telefone: <a href="tel:<?= e((string) ($client['phoneHref'] ?? '')) ?>"><?= e((string) ($client['phone'] ?? '')) ?></a></p>
    <?php if (!empty($client['businessHours']) && is_array($client['businessHours'])): ?>
      <p>Seg-Sex: <?= e((string) ($client['businessHours']['weekday'] ?? '')) ?> | Sábado: <?= e((string) ($client['businessHours']['saturday'] ?? '')) ?> | Domingo: <?= e((string) ($client['businessHours']['sunday'] ?? '')) ?></p>
    <?php endif; ?>
    <?php if ($privacyUrl !== null || $termsUrl !== null): ?>
      <p class="store-footer__legal-links">
        <?php if ($privacyUrl !== null): ?>
          <a href="<?= e($privacyUrl) ?>">Política de Privacidade</a>
        <?php endif; ?>
        <?php if ($privacyUrl !== null && $termsUrl !== null): ?>
          <span aria-hidden="true"> · </span>
        <?php endif; ?>
        <?php if ($termsUrl !== null): ?>
          <a href="<?= e($termsUrl) ?>">Termos de Uso</a>
        <?php endif; ?>
      </p>
    <?php endif; ?>
  </div>
</footer>
