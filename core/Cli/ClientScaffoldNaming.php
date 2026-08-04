<?php

declare(strict_types=1);

namespace Core\Cli;

/**
 * Converte slug de tenant em nome de pasta/namespace PSR-4 (Clients\{Folder}\).
 */
final class ClientScaffoldNaming
{
    public static function slugToFolder(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $parts = preg_split('/[^a-z0-9]+/', $slug) ?: [];
        $parts = array_filter($parts, static fn (string $p): bool => $p !== '');

        if ($parts === []) {
            return 'Tenant';
        }

        return implode('', array_map(static fn (string $p): string => ucfirst($p), $parts));
    }

    public static function orderReferencePrefix(string $slug): string
    {
        $clean = strtoupper(preg_replace('/[^a-z0-9]/', '', strtolower($slug)) ?? '');
        if ($clean === '') {
            return 'LOJA';
        }

        return substr($clean, 0, 6);
    }
}
