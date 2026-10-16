<?php
$data = $data ?? [];
$errors = $errors ?? [];
$action = $action ?? url('/admin/frete/criar');
$moneyValue = static function ($value): string {
    if ($value === null || $value === '') {
        return '';
    }

    return is_numeric($value) ? number_format((float) $value, 2, ',', '.') : (string) $value;
};
$cep = static function ($value): string {
    $digits = preg_replace('/\D+/', '', (string) $value) ?? '';

    return strlen($digits) === 8 ? substr($digits, 0, 5) . '-' . substr($digits, 5) : (string) $value;
};
?>
<div class="mb-3">
    <a href="<?= url('/admin/frete') ?>" class="btn btn-secondary">← Voltar</a>
</div>

<?php if ($errors !== []): ?>
    <div class="alerta alerta-erro">
        <?php foreach ($errors as $error): ?>
            <div>⚠️ <?= e($error) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card-admin card-body">
    <form method="POST" action="<?= e($action) ?>">
        <?= csrf_field() ?>

        <div class="form-row">
            <div class="form-grupo">
                <label for="name">Nome da zona *</label>
                <input class="form-control" type="text" id="name" name="name" value="<?= e((string) ($data['name'] ?? '')) ?>" placeholder="Ex.: Rincão — Centro" required />
            </div>
            <div class="form-grupo">
                <label for="city">Cidade *</label>
                <input class="form-control" type="text" id="city" name="city" value="<?= e((string) ($data['city'] ?? '')) ?>" placeholder="Rincão" required />
            </div>
            <div class="form-grupo">
                <label for="state">UF *</label>
                <input class="form-control" type="text" id="state" name="state" value="<?= e((string) ($data['state'] ?? 'SP')) ?>" maxlength="2" required />
            </div>
        </div>

        <div class="form-grupo">
            <label for="neighborhoods">Bairros atendidos (opcional)</label>
            <textarea class="form-control" id="neighborhoods" name="neighborhoods" rows="4" placeholder="Um bairro por linha. Deixe vazio para atender a cidade inteira."><?= e((string) ($data['neighborhoods'] ?? '')) ?></textarea>
            <small class="text-muted">Abreviações comuns (Jd., Vl., Pq.) e acentos são reconhecidos automaticamente.</small>
        </div>

        <div class="form-row">
            <div class="form-grupo">
                <label for="postal_code_start">Faixa de CEP — início (opcional)</label>
                <input class="form-control" type="text" id="postal_code_start" name="postal_code_start" value="<?= e($cep($data['postal_code_start'] ?? '')) ?>" placeholder="14830-000" inputmode="numeric" />
            </div>
            <div class="form-grupo">
                <label for="postal_code_end">Faixa de CEP — fim</label>
                <input class="form-control" type="text" id="postal_code_end" name="postal_code_end" value="<?= e($cep($data['postal_code_end'] ?? '')) ?>" placeholder="14839-999" inputmode="numeric" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-grupo">
                <label for="fee">Valor do frete (R$) *</label>
                <input class="form-control" type="text" id="fee" name="fee" value="<?= e($moneyValue($data['fee'] ?? '')) ?>" placeholder="0,00 = grátis" inputmode="decimal" required />
            </div>
            <div class="form-grupo">
                <label for="free_shipping_min">Frete grátis acima de (R$)</label>
                <input class="form-control" type="text" id="free_shipping_min" name="free_shipping_min" value="<?= e($moneyValue($data['free_shipping_min'] ?? '')) ?>" inputmode="decimal" />
            </div>
            <div class="form-grupo">
                <label for="min_order_amount">Pedido mínimo para entrega (R$)</label>
                <input class="form-control" type="text" id="min_order_amount" name="min_order_amount" value="<?= e($moneyValue($data['min_order_amount'] ?? '')) ?>" inputmode="decimal" />
            </div>
        </div>

        <div class="form-row">
            <div class="form-grupo">
                <label for="lead_time">Prazo exibido ao cliente</label>
                <input class="form-control" type="text" id="lead_time" name="lead_time" value="<?= e((string) ($data['lead_time'] ?? '')) ?>" placeholder="Ex.: Entrega em até 1 dia útil" maxlength="80" />
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

        <button type="submit" class="btn btn-success">Salvar zona</button>
        <a href="<?= url('/admin/frete') ?>" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
