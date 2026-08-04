<?php
$displayRef = (string) ($displayRef ?? '');
$customerName = (string) ($customerName ?? '');
$trackingUrl = (string) ($trackingUrl ?? '');
$items = $items ?? [];
$totalFormatted = (string) ($totalFormatted ?? '');
?>
<p>Olá<?= $customerName !== '' ? ' <strong>' . e($customerName) . '</strong>' : '' ?>,</p>

<p>Recebemos seu pedido <strong><?= e($displayRef) ?></strong> com sucesso.</p>

<p>Nossa equipe vai confirmar preços, estoque e condições de entrega ou retirada. Em seguida entraremos em contato pelo telefone ou WhatsApp informados no pedido.</p>

<p><strong>Status:</strong> Aguardando confirmação da loja</p>

<?php if ($trackingUrl !== ''): ?>
  <p style="margin:20px 0;">
    <a href="<?= e($trackingUrl) ?>" style="display:inline-block;padding:10px 16px;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:6px;font-weight:bold;">
      Acompanhar pedido
    </a>
  </p>
<?php endif; ?>

<?php if ($items !== []): ?>
  <h3 style="margin:24px 0 12px;font-size:16px;">Resumo do pedido</h3>
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;font-size:14px;">
    <?php foreach ($items as $item): ?>
      <tr>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;">
          <?= (int) ($item['quantity'] ?? 0) ?>× <?= e((string) ($item['product_name'] ?? 'Produto')) ?>
        </td>
        <td style="padding:8px 0;border-bottom:1px solid #e5e7eb;text-align:right;white-space:nowrap;">
          <?= e(money((float) ($item['total_amount'] ?? 0))) ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <tr>
      <td style="padding:12px 0;font-weight:bold;">Total indicativo</td>
      <td style="padding:12px 0;font-weight:bold;text-align:right;"><?= e($totalFormatted) ?></td>
    </tr>
  </table>
  <p style="font-size:13px;color:#6b7280;">Valores e disponibilidade serão confirmados pela loja.</p>
<?php endif; ?>
