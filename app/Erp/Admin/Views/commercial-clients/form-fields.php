<label>
    Nome
    <input class="form-control" type="text" name="name" value="<?= e((string) ($data['name'] ?? '')) ?>" required>
</label>
<label>
    Documento
    <input class="form-control" type="text" name="document" value="<?= e((string) ($data['document'] ?? '')) ?>">
</label>
<label>
    E-mail
    <input class="form-control" type="email" name="email" value="<?= e((string) ($data['email'] ?? '')) ?>">
</label>
<label>
    Telefone
    <input class="form-control" type="text" name="phone" value="<?= e((string) ($data['phone'] ?? '')) ?>">
</label>
<label>
    Endereço
    <input class="form-control" type="text" name="address" value="<?= e((string) ($data['address'] ?? '')) ?>">
</label>
<label>
    Status
    <?php $status = (string) ($data['status'] ?? 'active'); ?>
    <select class="form-control" name="status">
        <option value="active"<?= $status === 'active' ? ' selected' : '' ?>>Ativo</option>
        <option value="inactive"<?= $status === 'inactive' ? ' selected' : '' ?>>Inativo</option>
    </select>
</label>
