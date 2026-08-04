<?php

declare(strict_types=1);

namespace Tests\Support;

use Erp\Notifications\Contracts\MailTransportInterface;

final class RecordingMailTransport implements MailTransportInterface
{
    /** @var list<array{to: string, subject: string, html: string, text: ?string, fromName: ?string}> */
    public array $messages = [];

    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $fromName = null
    ): bool {
        $this->messages[] = [
            'to' => $to,
            'subject' => $subject,
            'html' => $htmlBody,
            'text' => $textBody,
            'fromName' => $fromName,
        ];

        return true;
    }
}
