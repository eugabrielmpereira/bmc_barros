<?php
$logs = $logs ?? [];
?>
<?php partial('page-header', [
    'subtitle' => count($logs) . ' evento(s) auditado(s)',
]); ?>

<div class="card-admin card-body" style="padding:0">
    <div class="table-responsive">
        <table class="table-admin">
            <thead><tr><th>Ação</th><th>Entidade</th><th>ID</th><th>Usuário</th><th>Metadados</th><th>Data</th></tr></thead>
            <tbody>
            <?php if ($logs === []): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">Nenhum evento auditado.</td></tr>
            <?php else: ?>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><span class="badge-pill badge-pill-origin"><?= e((string) $log['action']) ?></span></td>
                        <td><?= e((string) $log['entity']) ?></td>
                        <td><span class="order-meta-ref"><?= isset($log['entity_id']) ? (int) $log['entity_id'] : '—' ?></span></td>
                        <td><?= e((string) ($log['user_name'] ?? 'Sistema')) ?></td>
                        <td><small class="text-muted"><?= e((string) ($log['metadata'] ?? '')) ?></small></td>
                        <td><?= e(function_exists('datetime_br') ? datetime_br((string) ($log['created_at'] ?? '')) : (string) ($log['created_at'] ?? '')) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
