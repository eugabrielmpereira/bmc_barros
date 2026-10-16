<?php
$client = $client ?? client_config();
$customer = $customer ?? [];
$addresses = $addresses ?? [];
$errors = $errors ?? [];
$fieldErrors = $field_errors ?? [];
$data = $data ?? [];
$msg = $msg ?? null;
$tipo = $tipo ?? 'ok';
$clientName = (string) ($client['name'] ?? 'Cliente');
?>
<!doctype html>
<html lang="pt-BR">
  <head>
    <?php
      $pageTitle = 'Endereços';
      $noindex = true;
      require __DIR__ . '/../partials/head.php';
    ?>
  </head>
  <body>
    <?php site_topbar(['client' => $client]); ?>

    <main id="conteudo" class="commerce-page">
      <div class="commerce-page-header">
        <p class="eyebrow">Minha conta</p>
        <h1>Endereços salvos</h1>
        <p>Cadastre endereços para agilizar o checkout.</p>
      </div>

      <?php if ($msg): ?>
        <div class="commerce-alert commerce-alert--<?= e(commerce_alert_modifier((string) $tipo)) ?>"><?= e((string) $msg) ?></div>
      <?php endif; ?>

      <section class="commerce-layout commerce-layout--single">
        <div class="catalog-main">
          <nav class="account-subnav">
            <a href="<?= BASE_URL ?>/meus-pedidos">Pedidos</a>
            <a href="<?= BASE_URL ?>/minha-conta">Dados pessoais</a>
            <a href="<?= BASE_URL ?>/minha-conta/enderecos" aria-current="page">Endereços</a>
          </nav>

          <?php foreach ($errors as $error): ?>
            <div class="commerce-alert commerce-alert--error"><?= e((string) $error) ?></div>
          <?php endforeach; ?>

          <?php if ($addresses !== []): ?>
            <div class="commerce-order-list">
              <?php foreach ($addresses as $address): ?>
                <article class="commerce-order-card">
                  <div>
                    <strong><?= e((string) ($address['label'] ?? 'Endereço')) ?></strong>
                    <?php if ((int) ($address['is_default'] ?? 0) === 1): ?>
                      <span class="commerce-tag">Padrão</span>
                    <?php endif; ?>
                    <p>
                      <?= e((string) ($address['street'] ?? '')) ?>, <?= e((string) ($address['street_number'] ?? '')) ?>
                      — <?= e((string) ($address['neighborhood'] ?? '')) ?>,
                      <?= e((string) ($address['city'] ?? '')) ?>/<?= e((string) ($address['state'] ?? '')) ?>
                    </p>
                  </div>
                  <div class="commerce-order-card__actions">
                    <?php if ((int) ($address['is_default'] ?? 0) !== 1): ?>
                      <form method="POST" action="<?= BASE_URL ?>/minha-conta/enderecos/<?= (int) ($address['id'] ?? 0) ?>/padrao">
                        <?= csrf_field() ?>
                        <button type="submit" class="btn secondary">Tornar padrão</button>
                      </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= BASE_URL ?>/minha-conta/enderecos/<?= (int) ($address['id'] ?? 0) ?>/remover" onsubmit="return confirm('Remover este endereço?');">
                      <?= csrf_field() ?>
                      <button type="submit" class="btn secondary">Remover</button>
                    </form>
                  </div>
                </article>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <h2>Novo endereço</h2>
          <form method="POST" action="<?= BASE_URL ?>/minha-conta/enderecos" class="commerce-form">
            <?= csrf_field() ?>

            <label>Identificação (ex.: Casa, Trabalho)
              <input type="text" name="label" value="<?= e((string) ($data['label'] ?? 'Principal')) ?>" />
            </label>

            <?php require SHARED_PATH . '/View/site/checkout-address-fields.php'; ?>

            <label class="commerce-checkbox">
              <input type="checkbox" name="is_default" value="1" <?= !empty($data['is_default']) ? 'checked' : '' ?> />
              Definir como endereço padrão
            </label>

            <div class="commerce-form-actions">
              <button type="submit" class="btn primary">Salvar endereço</button>
            </div>
          </form>
        </div>
      </section>
    </main>

    <?php commerce_footer_minimal(); ?>
    <?php site_nav_scripts(); ?>
  </body>
</html>
