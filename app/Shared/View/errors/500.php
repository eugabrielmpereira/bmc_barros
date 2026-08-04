<?php
/** @var string $message */
/** @var int $code */
/** @var string|null $debug */
$errorCss = rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/') . '/assets/shared/errors.css';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= (int) $code ?> — Erro interno</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($errorCss, ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body>
    <div class="error-page__box">
        <h1 class="error-page__code error-page__code--warning"><?= (int) $code ?></h1>
        <p class="error-page__message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <?php if (!empty($debug)): ?>
            <pre class="error-page__debug"><?= htmlspecialchars((string) $debug, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php endif; ?>
        <p class="error-page__actions">
            <a href="<?= htmlspecialchars(rtrim((string) (defined('BASE_URL') ? BASE_URL : '/'), '/') . '/', ENT_QUOTES, 'UTF-8') ?>">Voltar à página inicial</a>
        </p>
    </div>
</body>
</html>
