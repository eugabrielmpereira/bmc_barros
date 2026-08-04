<?php

declare(strict_types=1);

namespace Core\Security;

/**
 * Limita consultas públicas de pedido por IP (contador em sessão).
 */
final class OrderTrackingRateLimiter
{
    private const SESSION_KEY = 'order_tracking_attempts';

    private const MAX_ATTEMPTS = 10;

    private const WINDOW_SECONDS = 600;

    public function isBlocked(string $ip): bool
    {
        $attempts = $this->readAttempts();
        $this->purgeExpired($attempts);
        $key = $this->normalizeIp($ip);

        return count($attempts[$key] ?? []) >= self::MAX_ATTEMPTS;
    }

    public function recordAttempt(string $ip): void
    {
        $attempts = $this->readAttempts();
        $this->purgeExpired($attempts);
        $key = $this->normalizeIp($ip);
        $attempts[$key][] = time();
        $_SESSION[self::SESSION_KEY] = $attempts;
    }

    /**
     * @return array<string, list<int>>
     */
    private function readAttempts(): array
    {
        $stored = $_SESSION[self::SESSION_KEY] ?? [];

        return is_array($stored) ? $stored : [];
    }

    /**
     * @param array<string, list<int>> $attempts
     */
    private function purgeExpired(array &$attempts): void
    {
        $cutoff = time() - self::WINDOW_SECONDS;

        foreach ($attempts as $ip => $timestamps) {
            $attempts[$ip] = array_values(array_filter(
                $timestamps,
                static fn (int $timestamp): bool => $timestamp >= $cutoff
            ));
            if ($attempts[$ip] === []) {
                unset($attempts[$ip]);
            }
        }
    }

    private function normalizeIp(string $ip): string
    {
        $ip = trim($ip);

        return $ip !== '' ? $ip : 'unknown';
    }
}
