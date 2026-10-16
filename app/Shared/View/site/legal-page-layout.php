<?php

declare(strict_types=1);

/** @var array<string, mixed> $client */
/** @var string $pageTitle */
/** @var string $legalContentPartial */
$client = $client ?? client_config();
$clientName = (string) ($client['name'] ?? 'Cliente');
$pageTitle = (string) ($pageTitle ?? 'Documento legal');
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php $clientHead = client_partial_override('head'); ?>
    <?php if ($clientHead !== null): ?>
      <?php require $clientHead; ?>
    <?php else: ?>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($pageTitle) ?> | <?= e($clientName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@400;700;800;900&family=Oswald:wght@500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
    <?php endif; ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="legal-page">
      <header class="legal-page__header">
        <p class="eyebrow"><?= e($clientName) ?></p>
        <h1><?= e($pageTitle) ?></h1>
        <p class="legal-page__updated">Última atualização: <?= e(date('d/m/Y')) ?> — versão preliminar sujeita a revisão jurídica.</p>
      </header>

      <article class="legal-page__content">
        <?php require SHARED_PATH . '/View/site/legal-controller-block.php'; ?>
        <?php require $legalContentPartial; ?>
      </article>
    </main>

    <?php site_store_footer($client); ?>
    <?php commerce_assets(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
