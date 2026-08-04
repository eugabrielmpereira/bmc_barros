<?php
$storeName = (string) ($storeName ?? 'Loja');
$content = (string) ($content ?? '');
?>
<!DOCTYPE html>
<html lang="pt-BR">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= e($storeName) ?></title>
  </head>
  <body style="margin:0;padding:0;background:#f4f6f8;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f6f8;padding:24px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e7eb;">
            <tr>
              <td style="padding:20px 24px;background:#111827;color:#ffffff;font-size:18px;font-weight:bold;">
                <?= e($storeName) ?>
              </td>
            </tr>
            <tr>
              <td style="padding:24px;font-size:15px;line-height:1.6;">
                <?= $content ?>
              </td>
            </tr>
            <tr>
              <td style="padding:16px 24px;background:#f9fafb;font-size:12px;color:#6b7280;border-top:1px solid #e5e7eb;">
                Mensagem automática. Não responda este e-mail.
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
