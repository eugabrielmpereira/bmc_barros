<?php

declare(strict_types=1);

namespace Core\Security;

use RuntimeException;

/**
 * Criptografia simétrica de segredos no banco mestre (APP_KEY).
 * Preferência: libsodium; fallback OpenSSL AES-256-GCM (XAMPP sem ext-sodium).
 */
final class SecretCryptor
{
    private const PREFIX_SODIUM = 'enc:v1:';

    private const PREFIX_OPENSSL = 'enc:v1o:';

    public static function isEncrypted(string $value): bool
    {
        return str_starts_with($value, self::PREFIX_SODIUM)
            || str_starts_with($value, self::PREFIX_OPENSSL);
    }

    public static function encrypt(string $plainText): string
    {
        $plainText = trim($plainText);
        if ($plainText === '') {
            return '';
        }

        if (self::isEncrypted($plainText)) {
            return $plainText;
        }

        if (function_exists('sodium_crypto_secretbox')) {
            return self::encryptSodium($plainText);
        }

        return self::encryptOpenSsl($plainText);
    }

    public static function decrypt(string $stored): string
    {
        $stored = trim($stored);
        if ($stored === '') {
            return '';
        }

        if (!self::isEncrypted($stored)) {
            return $stored;
        }

        if (str_starts_with($stored, self::PREFIX_SODIUM)) {
            return self::decryptSodium($stored);
        }

        return self::decryptOpenSsl($stored);
    }

    public static function encryptIfPossible(string $plainText): string
    {
        if (trim($plainText) === '' || self::appKey() === '') {
            return trim($plainText);
        }

        return self::encrypt($plainText);
    }

    public static function appKey(): string
    {
        return trim((string) env('APP_KEY', ''));
    }

    private static function encryptSodium(string $plainText): string
    {
        $key = self::deriveKeySodium();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = sodium_crypto_secretbox($plainText, $nonce, $key);

        return self::PREFIX_SODIUM . base64_encode($nonce . $cipher);
    }

    private static function decryptSodium(string $stored): string
    {
        $payload = base64_decode(substr($stored, strlen(self::PREFIX_SODIUM)), true);
        if ($payload === false || strlen($payload) < SODIUM_CRYPTO_SECRETBOX_NONCEBYTES + 1) {
            throw new RuntimeException('Segredo criptografado inválido.');
        }

        $nonce = substr($payload, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $cipher = substr($payload, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $plain = sodium_crypto_secretbox_open($cipher, $nonce, self::deriveKeySodium());

        if ($plain === false) {
            throw new RuntimeException('Falha ao descriptografar segredo (APP_KEY incorreta?).');
        }

        return $plain;
    }

    private static function encryptOpenSsl(string $plainText): string
    {
        $key = self::deriveKeyOpenSsl();
        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($plainText, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            throw new RuntimeException('Falha ao criptografar segredo (OpenSSL).');
        }

        return self::PREFIX_OPENSSL . base64_encode($iv . $tag . $cipher);
    }

    private static function decryptOpenSsl(string $stored): string
    {
        $payload = base64_decode(substr($stored, strlen(self::PREFIX_OPENSSL)), true);
        if ($payload === false || strlen($payload) < 12 + 16 + 1) {
            throw new RuntimeException('Segredo criptografado inválido.');
        }

        $iv = substr($payload, 0, 12);
        $tag = substr($payload, 12, 16);
        $cipher = substr($payload, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', self::deriveKeyOpenSsl(), OPENSSL_RAW_DATA, $iv, $tag);

        if ($plain === false) {
            throw new RuntimeException('Falha ao descriptografar segredo (APP_KEY incorreta?).');
        }

        return $plain;
    }

    private static function deriveKeySodium(): string
    {
        $appKey = self::requireAppKey();

        return sodium_crypto_generichash($appKey, '', SODIUM_CRYPTO_SECRETBOX_KEYBYTES);
    }

    private static function deriveKeyOpenSsl(): string
    {
        return hash('sha256', self::requireAppKey(), true);
    }

    private static function requireAppKey(): string
    {
        $appKey = self::appKey();
        if ($appKey === '') {
            throw new RuntimeException('APP_KEY é obrigatória para criptografar segredos.');
        }

        return $appKey;
    }
}
