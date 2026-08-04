<?php

declare(strict_types=1);

namespace Erp\Notifications\Services;

use Erp\Orders\Contracts\PublicOrderReceiptReader;
use Erp\Notifications\Support\EmailTemplateRenderer;
use Shared\Support\Client;
use Throwable;

final class OrderNotificationService
{
    public function __construct(
        private ?PublicOrderReceiptReader $orderReceiptReader = null,
        private ?MailService $mailService = null,
        private ?EmailTemplateRenderer $templateRenderer = null
    ) {
        $this->orderReceiptReader ??= new \Erp\Orders\Services\OrderService();
        $this->mailService ??= new MailService();
        $this->templateRenderer ??= new EmailTemplateRenderer();
    }

    public function notifyOnlineOrderCreated(int $orderId): void
    {
        if ($orderId <= 0 || !$this->mailService->isEnabled()) {
            return;
        }

        try {
            $receipt = $this->orderReceiptReader->getPublicReceipt($orderId);
            if ($receipt === null) {
                return;
            }

            $clientConfig = Client::config();
            $storeName = (string) ($clientConfig['name'] ?? 'Loja');
            $context = $this->buildContext($receipt, $storeName);

            $this->sendCustomerConfirmation($context, $storeName);
            $this->sendAdminAlert($context, $storeName);
        } catch (Throwable $e) {
            error_log('[order_notification] order=' . $orderId . ' ' . $e->getMessage());
        }
    }

    /**
     * @param array{order: array<string, mixed>, items: list<array<string, mixed>>} $receipt
     * @return array<string, mixed>
     */
    private function buildContext(array $receipt, string $storeName): array
    {
        $order = $receipt['order'];
        $orderId = (int) ($order['id'] ?? 0);
        $reference = trim((string) ($order['public_reference'] ?? ''));
        $displayRef = $reference !== '' ? $reference : ('#' . $orderId);
        $trackingUrl = BASE_URL . '/acompanhar-pedido' . ($reference !== '' ? '?ref=' . rawurlencode($reference) : '');
        $adminUrl = BASE_URL . '/admin/pedidos/' . $orderId;

        return [
            'storeName' => $storeName,
            'order' => $order,
            'items' => $receipt['items'],
            'orderId' => $orderId,
            'displayRef' => $displayRef,
            'trackingUrl' => $trackingUrl,
            'adminUrl' => $adminUrl,
            'customerName' => trim((string) ($order['client_name'] ?? '')),
            'customerEmail' => trim((string) ($order['client_email'] ?? '')),
            'customerPhone' => trim((string) ($order['client_phone'] ?? '')),
            'totalFormatted' => money((float) ($order['total_amount'] ?? 0)),
        ];
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendCustomerConfirmation(array $context, string $storeName): void
    {
        $email = (string) ($context['customerEmail'] ?? '');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $displayRef = (string) $context['displayRef'];
        $subject = "Pedido {$displayRef} recebido — {$storeName}";
        $htmlBody = $this->templateRenderer->render('customer-order-received', $context);
        $textBody = $this->buildCustomerTextBody($context);

        $this->mailService->send($email, $subject, $htmlBody, $textBody, $storeName);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function sendAdminAlert(array $context, string $storeName): void
    {
        $adminEmail = $this->mailService->adminRecipient();
        if ($adminEmail === '' || !filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $displayRef = (string) $context['displayRef'];
        $subject = "Novo pedido online {$displayRef}";
        $htmlBody = $this->templateRenderer->render('admin-new-order', $context);
        $textBody = $this->buildAdminTextBody($context);

        $this->mailService->send($adminEmail, $subject, $htmlBody, $textBody, $storeName);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildCustomerTextBody(array $context): string
    {
        $lines = [
            'Olá' . (($context['customerName'] ?? '') !== '' ? ' ' . $context['customerName'] : '') . ',',
            '',
            'Recebemos seu pedido ' . $context['displayRef'] . ' em ' . $context['storeName'] . '.',
            'Nossa equipe entrará em contato para confirmar valores, estoque e entrega.',
            '',
            'Acompanhe: ' . $context['trackingUrl'],
            '',
            'Resumo:',
        ];

        foreach ($context['items'] as $item) {
            $lines[] = sprintf(
                '- %dx %s — %s',
                (int) ($item['quantity'] ?? 0),
                (string) ($item['product_name'] ?? 'Produto'),
                money((float) ($item['total_amount'] ?? 0))
            );
        }

        $lines[] = '';
        $lines[] = 'Total indicativo: ' . $context['totalFormatted'];

        return implode("\n", $lines);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function buildAdminTextBody(array $context): string
    {
        $lines = [
            'Novo pedido online ' . $context['displayRef'],
            '',
            'Cliente: ' . ($context['customerName'] !== '' ? $context['customerName'] : '—'),
            'E-mail: ' . ($context['customerEmail'] !== '' ? $context['customerEmail'] : '—'),
            'Telefone: ' . ($context['customerPhone'] !== '' ? $context['customerPhone'] : '—'),
            'Total: ' . $context['totalFormatted'],
            '',
            'Abrir no admin: ' . $context['adminUrl'],
        ];

        return implode("\n", $lines);
    }
}
