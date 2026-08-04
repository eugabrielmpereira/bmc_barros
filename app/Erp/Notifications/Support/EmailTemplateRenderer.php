<?php

declare(strict_types=1);

namespace Erp\Notifications\Support;

use InvalidArgumentException;

final class EmailTemplateRenderer
{
    private const TEMPLATE_ROOT = ERP_PATH . '/Notifications/Views/emails';

    /**
     * @param array<string, mixed> $data
     */
    public function render(string $template, array $data = [], bool $useLayout = true): string
    {
        $content = $this->renderFile($template, $data);

        if (!$useLayout || $template === 'layout') {
            return $content;
        }

        return $this->renderFile('layout', array_merge($data, ['content' => $content]));
    }

    /**
     * @param array<string, mixed> $data
     */
    private function renderFile(string $template, array $data): string
    {
        $path = self::TEMPLATE_ROOT . '/' . $template . '.php';
        if (!is_file($path)) {
            throw new InvalidArgumentException("Template de e-mail \"{$template}\" não encontrado.");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        require $path;

        return (string) ob_get_clean();
    }
}
