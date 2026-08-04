<?php

declare(strict_types=1);

namespace Core\Security;

use Core\Database;
use PDO;
use PDOException;

/**
 * Limita tentativas de login por IP (janela deslizante em segundos).
 */
final class LoginRateLimiter
{
    private const MAX_ATTEMPTS = 5;

    private const WINDOW_SECONDS = 300;

    private static bool $loggedMissingTable = false;

    public function __construct(private ?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::getInstance();
    }

    public function isBlocked(string $ip): bool
    {
        $ip = $this->normalizeIp($ip);
        $since = date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);

        try {
            $stmt = $this->pdo->prepare(
                'SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND created_at > ?'
            );
            $stmt->execute([$ip, $since]);

            return (int) $stmt->fetchColumn() >= self::MAX_ATTEMPTS;
        } catch (PDOException $e) {
            if ($this->isMissingAttemptsTable($e)) {
                $this->logMissingTableOnce();

                return false;
            }
            throw $e;
        }
    }

    public function recordFailure(string $ip): void
    {
        $ip = $this->normalizeIp($ip);
        $now = date('Y-m-d H:i:s');

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO login_attempts (ip_address, created_at) VALUES (?, ?)'
            );
            $stmt->execute([$ip, $now]);
        } catch (PDOException $e) {
            if ($this->isMissingAttemptsTable($e)) {
                $this->logMissingTableOnce();

                return;
            }
            throw $e;
        }
    }

    public function clearForIp(string $ip): void
    {
        $ip = $this->normalizeIp($ip);

        try {
            $stmt = $this->pdo->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
            $stmt->execute([$ip]);
        } catch (PDOException $e) {
            if ($this->isMissingAttemptsTable($e)) {
                $this->logMissingTableOnce();

                return;
            }
            throw $e;
        }
    }

    private function isMissingAttemptsTable(PDOException $e): bool
    {
        $msg = $e->getMessage();

        return str_contains($msg, '42S02')
            || str_contains($msg, "doesn't exist")
            || str_contains($msg, 'no such table');
    }

    private function logMissingTableOnce(): void
    {
        if (self::$loggedMissingTable) {
            return;
        }
        self::$loggedMissingTable = true;
        error_log(
            '[LoginRateLimiter] Tabela login_attempts inexistente; rate limit desativado. '
            . 'Execute: php migrate.php run'
        );
    }

    private function normalizeIp(string $ip): string
    {
        $ip = trim($ip);
        if ($ip === '') {
            return '0.0.0.0';
        }

        return substr($ip, 0, 45);
    }
}
