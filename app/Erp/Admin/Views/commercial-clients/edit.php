<?php
$data = $data ?? [];
$errors = $errors ?? [];
$clientId = (int) ($clientId ?? 0);
?>
<?php if ($errors !== []): ?><div class="alerta alerta-erro"><?php foreach ($errors as $error): ?><p><?= e((string) $error) ?></p><?php endforeach; ?></div><?php endif; ?>
<form method="POST" action="<?= url('/admin/clientes/editar/' . $clientId) ?>" class="card-admin card-body" data-confirm-submit="Salvar as alterações deste cliente?">
    <?= csrf_field() ?>
    <?php require __DIR__ . '/form-fields.php'; ?>
    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-success" type="submit">Salvar</button>
        <a class="btn btn-secondary" href="<?= url('/admin/clientes') ?>">Cancelar</a>
    </div>
</form>
