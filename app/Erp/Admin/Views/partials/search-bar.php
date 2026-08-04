<?php
/** @var string $action */
/** @var string $name */
/** @var string $value */
/** @var string $placeholder */
/** @var string|null $clearUrl */
$action = $action ?? '';
$name = $name ?? 'busca';
$value = $value ?? '';
$placeholder = $placeholder ?? 'Buscar...';
$clearUrl = $clearUrl ?? $action;
?>
<form method="GET" action="<?= e($action) ?>" class="search-inline mb-4">
    <input
        class="form-control search-inline__input"
        type="search"
        name="<?= e($name) ?>"
        value="<?= e($value) ?>"
        placeholder="<?= e($placeholder) ?>"
    />
    <button type="submit" class="btn btn-secondary search-inline__btn">Buscar</button>
    <?php if ($value !== ''): ?>
        <a href="<?= e($clearUrl) ?>" class="btn btn-outline-danger search-inline__clear btn-icon" title="Limpar busca" aria-label="Limpar busca">
            <i class="ti ti-x" aria-hidden="true"></i>
        </a>
    <?php endif; ?>
</form>
