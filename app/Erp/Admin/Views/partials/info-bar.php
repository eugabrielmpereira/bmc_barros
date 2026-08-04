<?php
/** @var string $message */
/** @var string $icon */
$message = $message ?? '';
$icon = $icon ?? 'ti-info-circle';
?>
<?php if ($message !== ''): ?>
<div class="info-bar mb-3">
    <i class="ti <?= e($icon) ?>" aria-hidden="true"></i>
    <span><?= e($message) ?></span>
</div>
<?php endif; ?>
