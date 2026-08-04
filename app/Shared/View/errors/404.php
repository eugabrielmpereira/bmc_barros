<?php
/** @var string $message */
/** @var int $code */
$errorCss = rtrim((string) (defined('BASE_URL') ? BASE_URL : ''), '/') . '/assets/shared/errors.css';
$homeUrl = rtrim((string) (defined('BASE_URL') ? BASE_URL : '/'), '/') . '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?= (int) $code ?> — Não encontrado</title>
    <link rel="stylesheet" href="<?= htmlspecialchars($errorCss, ENT_QUOTES, 'UTF-8') ?>" />
</head>
<body>
    <div class="error-page__box error-page__box--narrow">
        <h1 class="error-page__code error-page__code--not-found"><?= (int) $code ?></h1>
        <p class="error-page__message"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></p>
        <p class="error-page__actions"><a href="<?= htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8') ?>">Voltar à página inicial</a></p>
    </div>
</body>
</html>
