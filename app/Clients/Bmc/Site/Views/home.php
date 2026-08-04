<?php
$client = $client ?? client_config();
$clientName = (string) ($client['name'] ?? 'Cliente');
$clientLegalName = (string) ($client['legalName'] ?? $clientName);
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="<?= e($clientLegalName) ?>: materiais de construção, variedades, baterias, pesca, móveis, vestuário profissional e pagamento de boletos em Taquaral, Rincão - SP." />
    <title><?= e($clientLegalName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
  </head>
  <body>
    <?php site_topbar(['client' => $client, 'brandHref' => '#inicio']); ?>

    <main id="inicio">
      <section class="hero">
        <div class="hero-text">
          <p class="eyebrow">Tudo o que você precisa em um só lugar</p>
          <h1>Construção, variedades e atendimento de confiança perto de você.</h1>
          <p class="lead">A <strong><?= e($clientLegalName) ?></strong> atende Taquaral, Rincão e região com produtos para obra, casa, pesca, baterias, vestuário profissional e serviços do dia a dia.</p>
          <div class="actions">
            <a class="btn primary" href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Vim pelo site e gostaria de atendimento.')) ?>" target="_blank" rel="noreferrer">Chamar no WhatsApp</a>
            <a class="btn secondary" href="tel:<?= e((string) $client['phoneHref']) ?>">Ligar <?= e((string) $client['phone']) ?></a>
          </div>
          <div class="chips">
            
            <span>Grande variedade</span>
            <span>Obra, casa e serviços</span>
          </div>
        </div>
        <div class="hero-images">
          <img class="store-photo" src="<?= BASE_URL . e((string) $client['assets']['storePhoto']) ?>" alt="Fachada da loja <?= e($clientName) ?>" />
          <img class="product-photo" src="<?= BASE_URL . e((string) $client['assets']['heroProducts']) ?>" alt="Produtos e materiais de construção" />
        </div>
      </section>

      <section class="quick-contact">
        <div><strong>Precisa resolver agora?</strong><span>Fale com a loja pelo WhatsApp ou ligue no mesmo número.</span></div>
        <div class="quick-links">
          <a href="<?= e(client_whatsapp_url('Olá, ' . $clientName . '! Vim pelo site e gostaria de atendimento.')) ?>" target="_blank" rel="noreferrer">WhatsApp <?= e((string) $client['phone']) ?></a>
          <a href="tel:<?= e((string) $client['phoneHref']) ?>">Ligar agora</a>
        </div>
      </section>

      <section class="section" id="produtos">
        <div class="section-title">
          <p class="eyebrow">Produtos e serviços</p>
          <h2>Da obra ao dia a dia, a <?= e($clientName) ?> reúne soluções práticas para a comunidade.</h2>
          <p>O catálogo combina materiais de construção, utilidades, lazer, automotivo e serviços financeiros.</p>
        </div>

        <div class="cards">
          <article><a href="<?= e(app_url('/produtos?categoria=construcao')) ?>"><span>🔨</span><h3>Construção</h3><p>Cimento, areia, brita, ferragens, ferramentas e acabamentos.</p></a></article>
          <article><a href="<?= e(app_url('/produtos?categoria=baterias')) ?>"><span>🔋</span><h3>Baterias</h3><p>Baterias automotivas e atendimento prático para seu veículo.</p></a></article>
          <article><a href="<?= e(app_url('/produtos?categoria=pesca')) ?>"><span>🎣</span><h3>Pesca</h3><p>Acessórios completos para pesca e lazer.</p></a></article>
          <article><a href="<?= e(app_url('/produtos?categoria=moveis-e-utilidades')) ?>"><span>🪑</span><h3>Móveis e utilidades</h3><p>Móveis para casa, jardim e itens do dia a dia.</p></a></article>
          <article><a href="<?= e(app_url('/produtos?categoria=vestuario-profissional')) ?>"><span>🥾</span><h3>Vestuário profissional</h3><p>Roupas, calçados, EPIs e uniformes para trabalhadores.</p></a></article>
          <article><a href="<?= e(app_url('/produtos?categoria=pagamentos')) ?>"><span>💳</span><h3>Pagamentos</h3><p>Totem e guichê para pagamento de boletos e contas.</p></a></article>
        </div>
      </section>

      <section class="section about" id="sobre">
        <div>
          <p class="eyebrow">Sobre a <?= e($clientName) ?></p>
          <h2>Uma loja de Rincão feita para atender com proximidade, variedade e rapidez.</h2>
        </div>
        <p>A <?= e($clientLegalName) ?> nasceu para facilitar a rotina de moradores, pedreiros, mestres de obras, pescadores e famílias que buscam atendimento prestativo e produtos variados sem complicação.</p>
      </section>

      <section class="section hours" id="horario">
        <p class="eyebrow">Horário de Funcionamento</p>
        <h2>Quando estamos abertos para você.</h2>
        <div class="hours-grid">
          <div class="hours-card"><strong>Segunda a Sexta</strong><p>08h às 18h</p></div>
          <div class="hours-card"><strong>Sábado</strong><p>08h às 17h</p></div>
          <div class="hours-card"><strong>Domingo</strong><p>Fechado</p></div>
          <div class="hours-card notice"><strong>⚠️ Feriados</strong><p>Podem sofrer alterações. Confirme pelo WhatsApp.</p></div>
        </div>
      </section>

      <section class="section location" id="localizacao">
        <div class="location-text">
          <p class="eyebrow">Como chegar</p>
          <h2>Clique no mapinha e abra a rota até a loja.</h2>
          <p>Estamos na <strong><?= e((string) $client['address']) ?></strong>.</p>
          <div class="actions">
            <a class="btn primary" id="maps-direction-btn" href="#">Abrir rota no Google Maps</a>
            <a class="btn secondary" href="<?= e(client_whatsapp_url('Olá, preciso de ajuda para chegar até a loja.')) ?>" target="_blank" rel="noreferrer">Pedir ajuda pelo WhatsApp</a>
          </div>
        </div>
        <a class="map-card" href="https://www.google.com/maps/search/?api=1&query=<?= rawurlencode((string) $client['address']) ?>" target="_blank" rel="noreferrer" aria-label="Abrir mapa da <?= e($clientName) ?>">
          <img src="<?= BASE_URL . e((string) $client['assets']['mapCard']) ?>" alt="Mapinha indicando localização da loja" />
          <span>Clique para abrir no Google Maps</span>
        </a>
      </section>
    </main>

    <?php site_store_footer($client); ?>

    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>