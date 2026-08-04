<?php

declare(strict_types=1);

namespace Core\Security;

/**
 * Proteção CSRF via token armazenado na sessão (um token por sessão até regenerar).
 */
final class Csrf
{
    /** Mesma chave usada historicamente nas views admin (compatível com gerarTokenCSRF). */
    private const SESSION_KEY = 'csrf';

    public static function token(): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Sessão não está ativa.');
        }

        if (empty($_SESSION[self::SESSION_KEY])) {
            self::regenerate();
        }

        return (string) $_SESSION[self::SESSION_KEY];
    }

    public static function regenerate(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        $bytes = defined('CSRF_TOKEN_LENGTH') ? (int) CSRF_TOKEN_LENGTH : 32;
        $bytes = max(16, min($bytes, 64));

        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes($bytes));
    }

    public static function validatePosted(): bool
    {
        $sent = $_POST['csrf'] ?? $_POST['csrf_token'] ?? '';
        if (!is_string($sent) || $sent === '') {
            return false;
        }

        $stored = $_SESSION[self::SESSION_KEY] ?? '';
        if ($stored === '' || !is_string($stored)) {
            return false;
        }

        return hash_equals($stored, $sent);
    }

    /**
     * Resposta genérica; não expõe detalhe técnico.
     */
    public static function requireValidPostOrAbort(): void
    {
        if (!self::validatePosted()) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'Não foi possível processar a solicitação. Atualize a página e tente novamente.';
            exit;
        }
    }
}
