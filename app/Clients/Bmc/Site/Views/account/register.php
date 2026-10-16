<?php
$client = $client ?? client_config();
$errors = $errors ?? [];
$fieldErrors = $field_errors ?? [];
$data = $data ?? [];
$clientName = (string) ($client['name'] ?? 'Cliente');
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php
      $pageTitle = 'Criar conta';
      $noindex = true;
      require __DIR__ . '/../partials/head.php';
    ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Minha conta</p>
        <h1>Criar conta</h1>
        <p>Cadastre-se para acompanhar pedidos e salvar endereços.</p>
      </div>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <?php foreach ($errors as $error): ?>
            <div class="commerce-alert commerce-alert--error"><?= e((string) $error) ?></div>
          <?php endforeach; ?>

          <form method="POST" action="<?= BASE_URL ?>/cadastro" class="commerce-form">
            <?= csrf_field() ?>

            <label>Nome completo *
              <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'name')) ?>" type="text" name="name" required value="<?= e((string) ($data['name'] ?? '')) ?>" />
              <?= commerce_checkout_field_error_html($fieldErrors, 'name') ?>
            </label>

            <div class="commerce-form-row commerce-form-row-2">
              <label>E-mail *
                <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'email')) ?>" type="email" name="email" required value="<?= e((string) ($data['email'] ?? '')) ?>" autocomplete="email" />
                <?= commerce_checkout_field_error_html($fieldErrors, 'email') ?>
              </label>
              <label>Telefone / WhatsApp *
                <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'phone')) ?>" type="text" name="phone" required value="<?= e((string) ($data['phone'] ?? '')) ?>" />
                <?= commerce_checkout_field_error_html($fieldErrors, 'phone') ?>
              </label>
            </div>

            <label>CPF/CNPJ *
              <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'document')) ?>" type="text" name="document" required value="<?= e((string) ($data['document'] ?? '')) ?>" />
              <?= commerce_checkout_field_error_html($fieldErrors, 'document') ?>
            </label>

            <div class="commerce-form-row commerce-form-row-2">
              <label>Senha *
                <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'password')) ?>" type="password" name="password" required minlength="8" autocomplete="new-password" />
                <?= commerce_checkout_field_error_html($fieldErrors, 'password') ?>
              </label>
              <label>Confirmar senha *
                <input class="<?= e(commerce_checkout_input_class($fieldErrors, 'password_confirmation')) ?>" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password" />
                <?= commerce_checkout_field_error_html($fieldErrors, 'password_confirmation') ?>
              </label>
            </div>

            <div class="commerce-form-actions">
              <button type="submit" class="btn primary">Cadastrar</button>
              <a class="btn secondary" href="<?= BASE_URL ?>/entrar">Já tenho conta</a>
            </div>
          </form>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
