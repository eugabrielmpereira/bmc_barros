<?php
/**
 * Rodapé da loja BMC (substitui rodapés compartilhados via client_partial_override).
 */
require_once __DIR__ . '/icons.php';

$client = $client ?? client_config();
$storeName = (string) ($client['name'] ?? 'Loja');
$legalName = (string) ($client['legalName'] ?? $storeName);
$cnpj = trim((string) ($client['cnpj'] ?? ''));
$hours = is_array($client['businessHours'] ?? null) ? $client['businessHours'] : [];
$privacyUrl = client_legal_page_url('privacy');
$termsUrl = client_legal_page_url('terms');
$whatsUrl = client_whatsapp_url('Olá, ' . $storeName . '! Vim pelo site e gostaria de atendimento.');
$mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) ($client['address'] ?? ''));
?>
<footer class="site-footer">
  <div class="wrap site-footer__grid">
    <div class="site-footer__brand">
      <img src="<?= e(app_url((string) $client['assets']['logo'])) ?>" alt="" width="56" height="56" loading="lazy" />
      <p><strong><?= e($legalName) ?></strong></p>
      <?php if ($cnpj !== ''): ?>
        <p>CNPJ <?= e($cnpj) ?></p>
      <?php endif; ?>
      <p><a href="<?= e($mapsUrl) ?>" target="_blank" rel="noreferrer"><?= e((string) ($client['address'] ?? '')) ?></a></p>
    </div>

    <div>
      <h2 class="site-footer__title">Atendimento</h2>
      <ul class="site-footer__list">
        <li><a href="<?= e($whatsUrl) ?>" target="_blank" rel="noreferrer">WhatsApp <?= e((string) ($client['phone'] ?? '')) ?></a></li>
        <li><a href="tel:<?= e((string) ($client['phoneHref'] ?? '')) ?>">Ligar para a loja</a></li>
        <?php if ($hours !== []): ?>
          <li>Segunda a sexta, <?= e((string) ($hours['weekday'] ?? '')) ?></li>
          <li>Sábado, <?= e((string) ($hours['saturday'] ?? '')) ?></li>
          <li>Domingo: <?= e(mb_strtolower((string) ($hours['sunday'] ?? ''), 'UTF-8')) ?></li>
        <?php endif; ?>
      </ul>
    </div>

    <div>
      <h2 class="site-footer__title">Comprar</h2>
      <ul class="site-footer__list">
        <li><a href="<?= e(app_url('/produtos')) ?>">Todos os produtos</a></li>
        <li><a href="<?= e(app_url('/#entrega')) ?>">Frete e entrega</a></li>
        <li><a href="<?= e(app_url('/acompanhar-pedido')) ?>">Acompanhar pedido</a></li>
        <li><a href="<?= e(app_url('/meus-pedidos')) ?>">Meus pedidos</a></li>
      </ul>
    </div>

    <div>
      <h2 class="site-footer__title">Pagamento</h2>
      <ul class="site-footer__list site-footer__pay">
        <li><?= bmc_icon('pix') ?> Pix</li>
        <li><?= bmc_icon('card') ?> Cartão de crédito</li>
      </ul>
      <?php if ($privacyUrl !== null || $termsUrl !== null): ?>
        <ul class="site-footer__list site-footer__legal">
          <?php if ($privacyUrl !== null): ?><li><a href="<?= e($privacyUrl) ?>">Política de privacidade</a></li><?php endif; ?>
          <?php if ($termsUrl !== null): ?><li><a href="<?= e($termsUrl) ?>">Termos de uso</a></li><?php endif; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>
  <p class="wrap site-footer__copy">© <?= date('Y') ?> <?= e($legalName) ?>. Preços e estoque sujeitos a confirmação da loja.</p>
</footer>

<a class="whatsapp-float" href="<?= e($whatsUrl) ?>" target="_blank" rel="noreferrer" aria-label="Falar com a loja no WhatsApp">
  <?= bmc_icon('whatsapp') ?>
</a>
