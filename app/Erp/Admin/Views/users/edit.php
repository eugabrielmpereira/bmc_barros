<?php
$data = $data ?? [];
$errors = $errors ?? [];
$userId = (int) ($userId ?? 0);
?>

<?php if ($errors !== []): ?>
    <div class="alerta alerta-erro">
        <?php foreach ($errors as $error): ?>
            <p><?= e((string) $error) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= url('/admin/usuarios/editar/' . $userId) ?>" class="card-admin card-body" data-confirm-submit="Salvar as alterações deste usuário?">
    <?= csrf_field() ?>

    <label>
        Nome
        <input class="form-control" type="text" name="name" value="<?= e((string) ($data['name'] ?? $data['nome'] ?? '')) ?>" required>
    </label>

    <label>
        E-mail
        <input class="form-control" type="email" name="email" value="<?= e((string) ($data['email'] ?? '')) ?>" required>
    </label>

    <label>
        Nova senha
        <input class="form-control" type="password" name="password" minlength="8" placeholder="Preencha apenas se quiser alterar">
    </label>

    <label>
        Papel
        <select class="form-control" name="role">
            <?php $role = (string) ($data['role'] ?? 'operator'); ?>
            <option value="admin"<?= $role === 'admin' ? ' selected' : '' ?>>Administrador</option>
            <option value="operator"<?= $role === 'operator' ? ' selected' : '' ?>>Operador</option>
        </select>
    </label>

    <label>
        Status
        <select class="form-control" name="status">
            <?php $status = (string) ($data['status'] ?? 'ativo'); ?>
            <option value="ativo"<?= $status === 'ativo' ? ' selected' : '' ?>>Ativo</option>
            <option value="inativo"<?= $status === 'inativo' ? ' selected' : '' ?>>Inativo</option>
        </select>
    </label>

    <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-success">Salvar</button>
        <a href="<?= url('/admin/usuarios') ?>" class="btn btn-secondary">Cancelar</a>
    </div>
</form>
