<?php
use Erp\Finance\Support\PaymentMethod;

$client = $client ?? client_config();
$featured = $featured ?? [];
$departments = $departments ?? [];
$zones = $zones ?? [];
$storeName = (string) ($client['name'] ?? 'Loja');
$legalName = (string) ($client['legalName'] ?? $storeName);
$hours = is_array($client['businessHours'] ?? null) ? $client['businessHours'] : [];
$whatsUrl = client_whatsapp_url('Olá, ' . $storeName . '! Vim pelo site e quero fazer um pedido.');
$mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode((string) ($client['address'] ?? ''));
$cardEnabled = PaymentMethod::cardEnabled();
$maxInstallments = PaymentMethod::maxInstallments();
$serviceArea = (string) ($client['serviceArea'] ?? 'região');

$pageTitle = '';
$pageDescription = (string) ($client['seoDescription'] ?? '');
$jsonLd = [
    '@context' => 'https://schema.org',
    '@type' => 'HardwareStore',
    'name' => $legalName,
    'url' => app_url(),
    'image' => app_url((string) ($client['assets']['ogImage'] ?? '')),
    'logo' => app_url((string) ($client['assets']['logoJpg'] ?? '')),
    'telephone' => (string) ($client['phoneHref'] ?? ''),
    'address' => [
        '@type' => 'PostalAddress',
        'streetAddress' => 'Av. Ver. José Antônio De Lima, 120',
        'addressLocality' => (string) ($client['city'] ?? ''),
        'addressRegion' => (string) ($client['state'] ?? ''),
        'addressCountry' => 'BR',
    ],
    'openingHoursSpecification' => [
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'], 'opens' => '08:00', 'closes' => '18:00'],
        ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '08:00', 'closes' => '17:00'],
    ],
    'paymentAccepted' => $cardEnabled ? 'Pix, Cartão de crédito' : 'Pix',
    'areaServed' => $serviceArea,
];
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php require __DIR__ . '/partials/head.php'; ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo">
      <section class="hero">
        <div class="wrap hero__grid">
          <div>
            <h1 class="display">Material de construção com entrega em <?= e((string) ($client['city'] ?? 'sua cidade')) ?> e região</h1>
            <p class="hero__lead">Do cimento ao acabamento: escolha online, pague por Pix<?= $cardEnabled ? ' ou cartão' : '' ?> e receba com o caminhão da loja. Se preferir, retire no balcão.</p>
            <div class="hero__actions">
              <a class="btn btn--primary btn--lg" href="<?= e(app_url('/produtos')) ?>">Ver produtos</a>
              <a class="btn btn--whats btn--lg" href="<?= e($whatsUrl) ?>" target="_blank" rel="noreferrer"><?= bmc_icon('whatsapp') ?> Pedir pelo WhatsApp</a>
            </div>
            <ul class="hero__facts">
              <li><?= bmc_icon('truck') ?> Entrega com caminhão próprio em <?= e($serviceArea) ?></li>
              <li><?= bmc_icon('store') ?> Retirada na loja sem custo</li>
              <li><?= bmc_icon($cardEnabled ? 'card' : 'pix') ?> <?= $cardEnabled ? 'Pix ou cartão em até ' . $maxInstallments . 'x sem juros' : 'Pagamento por Pix na hora' ?></li>
            </ul>
          </div>
          <figure class="hero__photo">
            <picture>
              <source media="(max-width: 860px)" srcset="<?= e(app_url((string) $client['assets']['storePhotoSmall'])) ?>" />
              <img src="<?= e(app_url((string) $client['assets']['storePhoto'])) ?>" alt="Fachada da loja <?= e($storeName) ?>, com a parede azul, faixa vermelha e churrasqueiras de tijolo expostas na calçada" width="1400" height="1050" fetchpriority="high" />
            </picture>
            <figcaption><?= bmc_icon('pin') ?> <?= e((string) ($client['address'] ?? '')) ?></figcaption>
          </figure>
        </div>
      </section>

      <?php if ($departments !== []): ?>
        <section class="section" aria-labelledby="departamentos-titulo">
          <div class="wrap">
            <div class="section-head">
              <h2 id="departamentos-titulo">Departamentos</h2>
              <a href="<?= e(app_url('/produtos')) ?>">Ver tudo</a>
            </div>
            <div class="departments">
              <?php foreach ($departments as $dept): ?>
                <?php
                  $children = array_slice(array_map(static fn (array $c): string => (string) $c['nome'], $dept['children'] ?? []), 0, 3);
                ?>
                <a class="department" href="<?= e(app_url('/produtos?categoria=' . rawurlencode((string) $dept['slug']))) ?>">
                  <?= bmc_icon(bmc_department_icon((string) $dept['nome'])) ?>
                  <strong><?= e((string) $dept['nome']) ?></strong>
                  <?php if ($children !== []): ?>
                    <span><?= e(implode(', ', $children)) ?></span>
                  <?php endif; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <?php if ($featured !== []): ?>
        <section class="section section--flush-top" aria-labelledby="destaques-titulo">
          <div class="wrap">
            <div class="section-head">
              <h2 id="destaques-titulo">Pronta entrega</h2>
              <a href="<?= e(app_url('/produtos')) ?>">Ver todos os produtos</a>
            </div>
            <div class="product-grid">
              <?php foreach ($featured as $produto): ?>
                <?php require __DIR__ . '/partials/product-card.php'; ?>
              <?php endforeach; ?>
            </div>
          </div>
        </section>
      <?php endif; ?>

      <section class="section delivery" id="entrega" aria-labelledby="entrega-titulo">
        <div class="wrap delivery__grid">
          <div class="delivery__intro">
            <?= bmc_icon('truck') ?>
            <h2 id="entrega-titulo" class="display">Frete e entrega</h2>
            <p>A entrega é feita pelo caminhão da loja. O frete é calculado pelo bairro ou pela cidade quando você informa o endereço no fechamento do pedido.</p>
            <p>Retirada na loja não tem custo: separamos o pedido e avisamos pelo WhatsApp quando estiver pronto.</p>
          </div>
          <div>
            <?php if ($zones === []): ?>
              <p>O valor do frete é combinado com a loja logo depois do pedido. <a href="<?= e($whatsUrl) ?>" target="_blank" rel="noreferrer">Consulte pelo WhatsApp</a>.</p>
            <?php else: ?>
              <table class="zone-table">
                <thead>
                  <tr>
                    <th scope="col">Onde entregamos</th>
                    <th scope="col">Frete</th>
                    <th scope="col">Prazo</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($zones as $zone): ?>
                    <?php
                      $zoneFee = (float) ($zone['fee'] ?? 0);
                      $zoneFree = $zone['free_shipping_min'] !== null && (float) $zone['free_shipping_min'] > 0 ? (float) $zone['free_shipping_min'] : null;
                      $zoneMin = $zone['min_order_amount'] !== null && (float) $zone['min_order_amount'] > 0 ? (float) $zone['min_order_amount'] : null;
                    ?>
                    <tr>
                      <td>
                        <strong><?= e((string) $zone['name']) ?></strong>
                        <small><?= e((string) $zone['city']) ?>/<?= e((string) $zone['state']) ?><?= $zoneMin !== null ? ', pedido mínimo ' . e(formatarPreco($zoneMin)) : '' ?></small>
                      </td>
                      <td>
                        <?php if ($zoneFee <= 0): ?>
                          <span class="is-free">Grátis</span>
                        <?php else: ?>
                          <strong><?= e(formatarPreco($zoneFee)) ?></strong>
                          <?php if ($zoneFree !== null): ?><small>grátis acima de <?= e(formatarPreco($zoneFree)) ?></small><?php endif; ?>
                        <?php endif; ?>
                      </td>
                      <td><?= e((string) ($zone['lead_time'] ?? '') ?: 'Combinado no pedido') ?></td>
                    </tr>
                  <?php endforeach; ?>
                  <tr>
                    <td><strong>Retirada na loja</strong><small><?= e((string) ($client['address'] ?? '')) ?></small></td>
                    <td><span class="is-free">Grátis</span></td>
                    <td>Avisamos quando estiver pronto</td>
                  </tr>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </section>

      <section class="section" id="loja" aria-labelledby="loja-titulo">
        <div class="wrap">
          <div class="section-head"><h2 id="loja-titulo">A loja</h2></div>
          <div class="store-info">
            <div class="surface store-info__item">
              <?= bmc_icon('pin') ?>
              <h3>Endereço</h3>
              <p><?= e((string) ($client['address'] ?? '')) ?></p>
              <a class="btn btn--ghost" href="<?= e($mapsUrl) ?>" target="_blank" rel="noreferrer">Abrir no mapa</a>
            </div>
            <div class="surface store-info__item">
              <?= bmc_icon('clock') ?>
              <h3>Horário de funcionamento</h3>
              <dl>
                <dt>Seg. a sex.</dt><dd><?= e((string) ($hours['weekday'] ?? '')) ?></dd>
                <dt>Sábado</dt><dd><?= e((string) ($hours['saturday'] ?? '')) ?></dd>
                <dt>Domingo</dt><dd><?= e((string) ($hours['sunday'] ?? '')) ?></dd>
              </dl>
              <p class="pay-note">Feriados podem ter horário diferente.</p>
            </div>
            <div class="surface store-info__item">
              <?= bmc_icon('phone') ?>
              <h3>Atendimento</h3>
              <p>Dúvidas sobre medidas, quantidade de material ou entrega? Fale com quem entende de obra.</p>
              <a class="btn btn--whats" href="<?= e($whatsUrl) ?>" target="_blank" rel="noreferrer"><?= bmc_icon('whatsapp') ?> <?= e((string) ($client['phone'] ?? '')) ?></a>
            </div>
          </div>
        </div>
      </section>
    </main>

    <?php site_store_footer($client); ?>
    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
