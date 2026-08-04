<?php

declare(strict_types=1);

namespace Core\Database;

/**
 * Hash determinístico para lookup de webhook Asaas no banco mestre.
 */
final class WebhookTokenHasher
{
    public static function hash(string $plainToken): string
    {
        return hash('sha256', trim($plainToken));
    }

    public static function matches(string $plainToken, string $storedHash): bool
    {
        $storedHash = trim($storedHash);
        if ($storedHash === '') {
            return false;
        }

        return hash_equals($storedHash, self::hash($plainToken));
    }
}
