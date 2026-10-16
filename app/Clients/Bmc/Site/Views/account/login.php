<?php
$client = $client ?? client_config();
$errors = $errors ?? [];
$fieldErrors = $field_errors ?? [];
$data = $data ?? [];
$redirect = $redirect ?? '/meus-pedidos';
$clientName = (string) ($client['name'] ?? 'Cliente');
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php
      $pageTitle = 'Entrar';
      $noindex = true;
      require __DIR__ . '/../partials/head.php';
    ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Minha conta</p>
        <h1>Entrar</h1>
        <p>Acesse seus pedidos e endereços salvos.</p>
      </div>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <?php foreach ($errors as $error): ?>
            <div class="commerce-alert commerce-alert--error"><?= e((string) $error) ?></div>
          <?php endforeach; ?>

          <form method="POST" action="<?= BASE_URL ?>/entrar" class="commerce-form">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= e((string) $redirect) ?>" />

            <label>E-mail *
              <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'email')) ?>" type="email" name="email" required value="<?= e((string) ($data['email'] ?? '')) ?>" autocomplete="email" />
              <?= commerce_checkout_field_error_html($fieldErrors, 'email') ?>
            </label>

            <label>Senha *
              <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'password')) ?>" type="password" name="password" required autocomplete="current-password" />
              <?= commerce_checkout_field_error_html($fieldErrors, 'password') ?>
            </label>

            <div class="commerce-form-actions">
              <button type="submit" class="btn primary">Entrar</button>
              <a class="btn secondary" href="<?= BASE_URL ?>/cadastro">Criar conta</a>
            </div>
          </form>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
