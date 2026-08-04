<?php
/** @var list<array{label: string, icon: string, type: string, href?: string, attrs?: array<string, string>, confirm?: string}> $items */
$items = $items ?? [];
$menuId = $menuId ?? ('actions-' . bin2hex(random_bytes(4)));
?>
<?php if ($items !== []): ?>
<div class="actions-menu" data-actions-menu>
    <button
        type="button"
        class="btn btn-secondary btn-sm btn-icon actions-menu__trigger"
        aria-haspopup="true"
        aria-expanded="false"
        aria-controls="<?= e($menuId) ?>"
        title="Ações"
    >
        <i class="ti ti-dots-vertical" aria-hidden="true"></i>
    </button>
    <div class="actions-menu__panel" id="<?= e($menuId) ?>" role="menu" hidden>
        <?php foreach ($items as $item): ?>
            <?php
            $type = (string) ($item['type'] ?? 'link');
            $icon = (string) ($item['icon'] ?? 'ti-point');
            $label = (string) ($item['label'] ?? '');
            $attrs = $item['attrs'] ?? [];
            $attrString = '';
            foreach ($attrs as $key => $val) {
                $attrString .= ' ' . e((string) $key) . '="' . e((string) $val) . '"';
            }
            $confirm = isset($item['confirm']) ? (string) $item['confirm'] : '';
            ?>
            <?php if ($type === 'button'): ?>
                <button type="button" class="actions-menu__item" role="menuitem"<?= $attrString ?><?= $confirm !== '' ? ' data-confirm-click="' . e($confirm) . '"' : '' ?>>
                    <i class="ti <?= e($icon) ?>" aria-hidden="true"></i>
                    <?= e($label) ?>
                </button>
            <?php elseif ($type === 'submit'): ?>
                <button type="submit" class="actions-menu__item actions-menu__item--danger" role="menuitem"<?= $attrString ?><?= $confirm !== '' ? ' data-confirm-click="' . e($confirm) . '"' : '' ?>>
                    <i class="ti <?= e($icon) ?>" aria-hidden="true"></i>
                    <?= e($label) ?>
                </button>
            <?php else: ?>
                <a class="actions-menu__item" role="menuitem" href="<?= e((string) ($item['href'] ?? '#')) ?>"<?= $attrString ?>>
                    <i class="ti <?= e($icon) ?>" aria-hidden="true"></i>
                    <?= e($label) ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>
