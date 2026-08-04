<?php

declare(strict_types=1);

namespace Erp\Notifications\Transport;

use Erp\Notifications\Config\MailConfig;
use Erp\Notifications\Contracts\MailTransportInterface;
use PHPMailer\PHPMailer\Exception as MailerException;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

final class SmtpMailTransport implements MailTransportInterface
{
    public function __construct(
        private readonly MailConfig $config
    ) {
    }

    public function send(
        string $to,
        string $subject,
        string $htmlBody,
        ?string $textBody = null,
        ?string $fromName = null
    ): bool {
        if (!$this->config->isConfigured()) {
            return false;
        }

        $mailer = new PHPMailer(true);

        try {
            $mailer->isSMTP();
            $mailer->Host = $this->config->host;
            $mailer->Port = $this->config->port;
            $mailer->SMTPAuth = true;
            $mailer->Username = $this->config->username;
            $mailer->Password = $this->config->password;
            $mailer->CharSet = PHPMailer::CHARSET_UTF8;
            $mailer->Timeout = 10;

            $encryption = $this->resolveEncryption();
            if ($encryption !== null) {
                $mailer->SMTPSecure = $encryption;
            }

            $senderName = $fromName !== null && $fromName !== ''
                ? $fromName
                : $this->config->fromName;

            $mailer->setFrom($this->config->fromAddress, $senderName !== '' ? $senderName : $this->config->fromAddress);
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $htmlBody;
            $mailer->AltBody = $textBody ?? strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

            $mailer->send();

            return true;
        } catch (MailerException $e) {
            error_log('[mail_transport] ' . $e->getMessage());

            return false;
        } catch (Throwable $e) {
            error_log('[mail_transport] ' . $e->getMessage());

            return false;
        }
    }

    private function resolveEncryption(): ?string
    {
        return match ($this->config->encryption) {
            'tls', 'starttls' => PHPMailer::ENCRYPTION_STARTTLS,
            'ssl', 'smtps' => PHPMailer::ENCRYPTION_SMTPS,
            'none', '' => null,
            default => PHPMailer::ENCRYPTION_STARTTLS,
        };
    }
}
