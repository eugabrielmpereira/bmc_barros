<?php

declare(strict_types=1);

/** @var array<string, mixed> $client */
$client = $client ?? client_config();
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
$cnpj = trim((string) ($client['cnpj'] ?? ''));
$address = trim((string) ($client['address'] ?? ''));
$phone = trim((string) ($client['phone'] ?? ''));
$privacyUrl = client_legal_page_url('privacy');
$termsUrl = client_legal_page_url('terms');
?>
<section class="legal-controller">
  <p><strong><?= e($clientLegalName) ?></strong></p>
  <?php if ($cnpj !== ''): ?>
    <p>CNPJ: <?= e($cnpj) ?></p>
  <?php endif; ?>
  <?php if ($address !== ''): ?>
    <p>Endereço: <?= e($address) ?></p>
  <?php endif; ?>
  <?php if ($phone !== ''): ?>
    <p>Telefone / WhatsApp: <?= e($phone) ?></p>
  <?php endif; ?>
</section>
