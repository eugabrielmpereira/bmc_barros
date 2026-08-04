<?php

declare(strict_types=1);

namespace Erp\Notifications\Transport;

use Erp\Notifications\Contracts\MailTransportInterface;

final class NullMailTransport implements MailTransportInterface
{
    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $fromName = null
    ): bool {
        return false;
    }
}
