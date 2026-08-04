<?php
$displayRef = (string) ($displayRef ?? '');
$customerName = (string) ($customerName ?? '');
$customerEmail = (string) ($customerEmail ?? '');
$customerPhone = (string) ($customerPhone ?? '');
$totalFormatted = (string) ($totalFormatted ?? '');
$adminUrl = (string) ($adminUrl ?? '');
$items = $items ?? [];
?>
<p>Um novo pedido online foi recebido pelo site.</p>

<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="font-size:14px;margin:16px 0;">
  <tr>
    <td style="padding:6px 0;color:#6b7280;width:120px;">Referência</td>
    <td style="padding:6px 0;"><strong><?= e($displayRef) ?></strong></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#6b7280;">Cliente</td>
    <td style="padding:6px 0;"><?= e($customerName !== '' ? $customerName : '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#6b7280;">E-mail</td>
    <td style="padding:6px 0;"><?= e($customerEmail !== '' ? $customerEmail : '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#6b7280;">Telefone</td>
    <td style="padding:6px 0;"><?= e($customerPhone !== '' ? $customerPhone : '—') ?></td>
  </tr>
  <tr>
    <td style="padding:6px 0;color:#6b7280;">Total</td>
    <td style="padding:6px 0;"><strong><?= e($totalFormatted) ?></strong></td>
  </tr>
</table>

<?php if ($items !== []): ?>
  <h3 style="margin:20px 0 10px;font-size:15px;">Itens</h3>
  <ul style="margin:0;padding-left:20px;font-size:14px;">
    <?php foreach ($items as $item): ?>
      <li style="margin-bottom:6px;">
        <?= (int) ($item['quantity'] ?? 0) ?>× <?= e((string) ($item['product_name'] ?? 'Produto')) ?>
        — <?= e(money((float) ($item['total_amount'] ?? 0))) ?>
      </li>
    <?php endforeach; ?>
  </ul>
<?php endif; ?>

<?php if ($adminUrl !== ''): ?>
  <p style="margin:24px 0 0;">
    <a href="<?= e($adminUrl) ?>" style="display:inline-block;padding:10px 16px;background:#111827;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">
      Abrir no admin
    </a>
  </p>
<?php endif; ?>
