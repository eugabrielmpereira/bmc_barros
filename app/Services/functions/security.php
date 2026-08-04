<?php

function loginRateLimit()
{
    $max = 5;
    $window = 60;

    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [];
    }

    $_SESSION['login_attempts'] = array_filter(
        $_SESSION['login_attempts'],
        fn($t) => $t > time() - $window
    );

    if (count($_SESSION['login_attempts']) >= $max) {
        die('Muitas tentativas. Aguarde.');
    }

    $_SESSION['login_attempts'][] = time();
}