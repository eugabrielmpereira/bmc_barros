<?php

declare(strict_types=1);

namespace Core\Security;

/**
 * Expira sessão autenticada após inatividade (SESSION_TIMEOUT em segundos).
 */
final class SessionGuard
{
    public static function enforce(): void
    {
        if (!isset($_SESSION['admin_id'])) {
            return;
        }

        $timeout = defined('SESSION_TIMEOUT') ? (int) SESSION_TIMEOUT : 1800;
        if ($timeout <= 0) {
            $_SESSION['last_activity'] = time();

            return;
        }

        $now = time();
        $last = (int) ($_SESSION['last_activity'] ?? $now);

        if ($now - $last > $timeout) {
            unset($_SESSION['admin_id'], $_SESSION['admin_nome'], $_SESSION['last_activity']);
            Csrf::regenerate();
            self::redirectIfAdminRequest();

            return;
        }

        $_SESSION['last_activity'] = $now;
    }

    private static function redirectIfAdminRequest(): void
    {
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '');
        $path = parse_url($uri, PHP_URL_PATH) ?: '';

        if (!str_contains($path, '/admin')) {
            return;
        }

        if (str_contains($path, '/admin/login')) {
            return;
        }

        if (!headers_sent()) {
            header('Location: ' . BASE_URL . '/admin/login?expirado=1');
            exit;
        }
    }
}
