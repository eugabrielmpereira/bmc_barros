<?php
/** @var string $subtitle */
/** @var string|null $actionUrl */
/** @var string $actionLabel */
/** @var string $actionIcon */
$subtitle = $subtitle ?? '';
$actionUrl = $actionUrl ?? null;
$actionLabel = $actionLabel ?? 'Novo';
$actionIcon = $actionIcon ?? 'ti-plus';
?>
<?php if ($subtitle !== '' || $actionUrl !== null): ?>
<div class="page-header">
    <?php if ($subtitle !== ''): ?>
        <p class="page-header__subtitle text-muted"><?= e($subtitle) ?></p>
    <?php else: ?>
        <span></span>
    <?php endif; ?>
    <?php if ($actionUrl !== null): ?>
        <a href="<?= e($actionUrl) ?>" class="btn btn-approve-solid">
            <i class="ti <?= e($actionIcon) ?>" aria-hidden="true"></i>
            <?= e($actionLabel) ?>
        </a>
    <?php endif; ?>
</div>
<?php endif; ?>
