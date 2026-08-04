<link rel="stylesheet" href="<?= shared_commerce_asset('commerce.css') ?>" />
<div
  id="commerce-config"
  class="commerce-config"
  hidden
  data-base-url="<?= e(app_url()) ?>"
  data-csrf-token="<?= e(csrf_token()) ?>"
></div>
<?php require SHARED_PATH . '/View/site/commerce-drawer.php'; ?>
<?php require SHARED_PATH . '/View/site/commerce-buy-now-modal.php'; ?>
<script src="<?= shared_commerce_asset('commerce.js') ?>" defer></script>
