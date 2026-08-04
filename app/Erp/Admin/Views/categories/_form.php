<?php
/** @var array<string, mixed> $data */
/** @var list<array<string, mixed>> $parents */
?>
<div class="form-row">
    <div class="form-grupo">
        <label for="nome">Nome *</label>
        <input class="form-control" type="text" id="nome" name="nome" value="<?= e((string) ($data['nome'] ?? '')) ?>" required />
    </div>
    <div class="form-grupo">
        <label for="slug">Slug (URL)</label>
        <input class="form-control" type="text" id="slug" name="slug" value="<?= e((string) ($data['slug'] ?? '')) ?>" placeholder="Gerado automaticamente se vazio" />
    </div>
</div>

<div class="form-row">
    <div class="form-grupo">
        <label for="parent_id">Categoria pai</label>
        <select class="form-control" id="parent_id" name="parent_id">
            <option value="">Nenhuma (departamento raiz)</option>
            <?php foreach ($parents as $parent): ?>
                <?php
                    $pid = (int) $parent['id'];
                    $plevel = (int) ($parent['level'] ?? 1);
                    $prefix = $plevel > 1 ? str_repeat('— ', $plevel - 1) : '';
                ?>
                <option value="<?= $pid ?>" <?= (int) ($data['parent_id'] ?? 0) === $pid ? 'selected' : '' ?>>
                    <?= e($prefix . (string) $parent['nome']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-grupo">
        <label for="sort_order">Ordem</label>
        <input class="form-control" type="number" id="sort_order" name="sort_order" value="<?= (int) ($data['sort_order'] ?? 0) ?>" min="0" />
    </div>
    <div class="form-grupo">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status">
            <option value="active" <?= ($data['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Ativa</option>
            <option value="inactive" <?= ($data['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inativa</option>
        </select>
    </div>
</div>

<div class="form-grupo">
    <label for="description">Descrição (vitrine)</label>
    <textarea class="form-control" id="description" name="description" rows="3"><?= e((string) ($data['description'] ?? '')) ?></textarea>
</div>

<div class="form-row">
    <div class="form-grupo">
        <label for="meta_title">Meta título (SEO)</label>
        <input class="form-control" type="text" id="meta_title" name="meta_title" value="<?= e((string) ($data['meta_title'] ?? '')) ?>" />
    </div>
    <div class="form-grupo">
        <label for="meta_description">Meta descrição (SEO)</label>
        <input class="form-control" type="text" id="meta_description" name="meta_description" value="<?= e((string) ($data['meta_description'] ?? '')) ?>" maxlength="500" />
    </div>
</div>
