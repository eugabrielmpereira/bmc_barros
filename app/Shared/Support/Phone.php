<?php

declare(strict_types=1);

namespace Shared\Support;

/**
 * Normalização de telefone para comparação em checkout e auditoria.
 */
final class Phone
{
    public static function normalize(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', trim($phone)) ?? '';
        if ($digits === '') {
            return '';
        }

        if (strlen($digits) >= 12 && str_starts_with($digits, '55')) {
            $digits = substr($digits, 2);
        }

        return $digits;
    }

    public static function lastFourDigits(string $phone): string
    {
        $digits = self::normalize($phone);

        return strlen($digits) >= 4 ? substr($digits, -4) : $digits;
    }

    public static function matchesLastFourDigits(string $phone, string $input): bool
    {
        $expected = self::lastFourDigits($phone);
        $provided = preg_replace('/\D+/', '', trim($input)) ?? '';

        return $expected !== '' && $provided !== '' && $expected === substr($provided, -4);
    }
}
