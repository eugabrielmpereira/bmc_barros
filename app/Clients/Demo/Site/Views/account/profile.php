<?php
$client = $client ?? client_config();
$customer = $customer ?? [];
$errors = $errors ?? [];
$fieldErrors = $field_errors ?? [];
$msg = $msg ?? null;
$tipo = $tipo ?? 'ok';
$clientName = (string) ($client['name'] ?? 'Cliente');
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Minha conta | <?= e($clientName) ?></title>
    <link rel="stylesheet" href="<?= client_asset('css/style.css') ?>" />
    <?php commerce_page_styles(); ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Minha conta</p>
        <h1>Dados pessoais</h1>
      </div>

      <?php if ($msg): ?>
        <div class="commerce-alert commerce-alert--<?= e(commerce_alert_modifier((string) $tipo)) ?>"><?= e((string) $msg) ?></div>
      <?php endif; ?>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <nav class="account-subnav">
            <a href="<?= BASE_URL ?>/meus-pedidos">Pedidos</a>
            <a href="<?= BASE_URL ?>/minha-conta" aria-current="page">Dados pessoais</a>
            <a href="<?= BASE_URL ?>/minha-conta/enderecos">Endereços</a>
          </nav>

          <?php foreach ($errors as $error): ?>
            <div class="commerce-alert commerce-alert--error"><?= e((string) $error) ?></div>
          <?php endforeach; ?>

          <form method="POST" action="<?= BASE_URL ?>/minha-conta" class="commerce-form">
            <?= csrf_field() ?>

            <label>Nome *
              <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'name')) ?>" type="text" name="name" required value="<?= e((string) ($customer['name'] ?? '')) ?>" />
              <?= commerce_checkout_field_error_html($fieldErrors, 'name') ?>
            </label>

            <label>E-mail
              <input type="email" value="<?= e((string) ($customer['email'] ?? '')) ?>" disabled />
            </label>

            <div class="commerce-form-row commerce-form-row-2">
              <label>Telefone *
                <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'phone')) ?>" type="text" name="phone" required value="<?= e((string) ($customer['phone'] ?? '')) ?>" />
                <?= commerce_checkout_field_error_html($fieldErrors, 'phone') ?>
              </label>
              <label>CPF/CNPJ *
                <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'document')) ?>" type="text" name="document" required value="<?= e((string) ($customer['document'] ?? '')) ?>" />
                <?= commerce_checkout_field_error_html($fieldErrors, 'document') ?>
              </label>
            </div>

            <div class="commerce-form-actions">
              <button type="submit" class="btn primary">Salvar</button>
            </div>
          </form>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
