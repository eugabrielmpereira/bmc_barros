<?php

declare(strict_types=1);

namespace Core\Cli;

use RuntimeException;

final class ComposerAutoloadRefresher
{
    public static function dumpAutoload(string $projectRoot): void
    {
        $composer = $projectRoot . '/composer.json';
        if (!is_file($composer)) {
            throw new RuntimeException('composer.json não encontrado para dump-autoload.');
        }

        $command = 'composer dump-autoload -d ' . escapeshellarg($projectRoot) . ' 2>&1';
        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'composer dump-autoload falhou: ' . implode(PHP_EOL, $output)
            );
        }
    }
}
