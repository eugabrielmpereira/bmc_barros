<?php

declare(strict_types=1);

namespace Erp\Notifications\Contracts;

interface MailTransportInterface
{
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $fromName = null
    ): bool;
}
