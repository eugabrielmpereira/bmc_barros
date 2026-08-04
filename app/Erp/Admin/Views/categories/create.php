<?php
$data = $data ?? [];
$errors = $errors ?? [];
$parents = $parents ?? [];
?>
<div class="mb-3">
    <a href="<?= url('/admin/categorias') ?>" class="btn btn-secondary">← Voltar</a>
</div>

<?php if ($errors !== []): ?>
    <div class="alerta alerta-erro">
        <?php foreach ($errors as $error): ?>
            <div>⚠️ <?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card-admin card-body">
    <form method="POST" action="<?= url('/admin/categorias/criar') ?>" data-confirm-submit="Cadastrar esta categoria?">
        <?= csrf_field() ?>
        <?php require __DIR__ . '/_form.php'; ?>
        <button type="submit" class="btn btn-success">Salvar Categoria</button>
        <a href="<?= url('/admin/categorias') ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
