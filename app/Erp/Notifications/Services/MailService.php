<?php

declare(strict_types=1);

namespace Erp\Notifications\Services;

use Erp\Notifications\Config\MailConfig;
use Erp\Notifications\Contracts\MailTransportInterface;
use Erp\Notifications\Transport\NullMailTransport;
use Erp\Notifications\Transport\SmtpMailTransport;

final class MailService
{
    public function __construct(
        private ?MailConfig $config = null,
        private ?MailTransportInterface $transport = null
    ) {
        $this->config ??= MailConfig::resolve();
        $this->transport ??= $this->buildTransport($this->config);
    }

    public function isEnabled(): bool
    {
        return $this->config->enabled && $this->config->isConfigured();
    }

    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $fromName = null
    ): bool {
        if (!$this->isEnabled()) {
            return false;
        }

        $to = trim($to);
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        return $this->transport->send($to, $subject, $htmlBody, $textBody, $fromName);
    }

    public function adminRecipient(): string
    {
        return $this->config->adminTo;
    }

    private function buildTransport(MailConfig $config): MailTransportInterface
    {
        if (!$config->enabled || !$config->isConfigured()) {
            return new NullMailTransport();
        }

        return new SmtpMailTransport($config);
    }
}
