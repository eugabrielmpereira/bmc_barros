<?php if (empty($GLOBALS['__commerce_css_in_head'])): ?>
<link rel="stylesheet" href="<?= shared_commerce_asset('commerce.css') ?>" />
<?php endif; ?>
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
