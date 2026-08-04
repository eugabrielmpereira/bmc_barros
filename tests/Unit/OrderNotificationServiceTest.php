<?php

declare(strict_types=1);

namespace Tests\Unit;

use Erp\Notifications\Config\MailConfig;
use Erp\Notifications\Services\MailService;
use Erp\Notifications\Services\OrderNotificationService;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakePublicOrderReceiptReader;
use Tests\Support\RecordingMailTransport;

final class OrderNotificationServiceTest extends TestCase
{
    public function testNotifyOnlineOrderCreatedSendsCustomerAndAdminEmails(): void
    {
        $receiptReader = new FakePublicOrderReceiptReader([
            'order' => [
                'id' => 42,
                'public_reference' => 'BMC-2026-0001',
                'client_name' => 'Maria Silva',
                'client_email' => 'maria@example.com',
                'client_phone' => '16999990000',
                'total_amount' => 150.5,
            ],
            'items' => [
                [
                    'quantity' => 2,
                    'product_name' => 'Cimento CP II',
                    'total_amount' => 150.5,
                ],
            ],
        ]);

        $transport = new RecordingMailTransport();
        $mailService = new MailService(
            new MailConfig(
                enabled: true,
                host: 'sandbox.smtp.mailtrap.io',
                port: 2525,
                username: 'user',
                password: 'pass',
                encryption: 'tls',
                fromAddress: 'noreply@example.com',
                fromName: 'Loja Teste',
                adminTo: 'admin@example.com'
            ),
            $transport
        );

        $service = new OrderNotificationService($receiptReader, $mailService);
        $service->notifyOnlineOrderCreated(42);

        $this->assertCount(2, $transport->messages);
        $this->assertSame('maria@example.com', $transport->messages[0]['to']);
        $this->assertStringContainsString('BMC-2026-0001', $transport->messages[0]['subject']);
        $this->assertStringContainsString('BMC-2026-0001', $transport->messages[0]['html']);
        $this->assertSame('admin@example.com', $transport->messages[1]['to']);
        $this->assertStringContainsString('Novo pedido online', $transport->messages[1]['subject']);
    }

    public function testNotifySkipsWhenMailDisabled(): void
    {
        $receiptReader = new FakePublicOrderReceiptReader([
            'order' => ['id' => 10, 'client_email' => 'test@example.com'],
            'items' => [],
        ]);

        $transport = new RecordingMailTransport();
        $mailService = new MailService(
            new MailConfig(
                enabled: false,
                host: 'sandbox.smtp.mailtrap.io',
                port: 2525,
                username: 'user',
                password: 'pass',
                encryption: 'tls',
                fromAddress: 'noreply@example.com',
                fromName: '',
                adminTo: 'admin@example.com'
            ),
            $transport
        );

        $service = new OrderNotificationService($receiptReader, $mailService);
        $service->notifyOnlineOrderCreated(10);

        $this->assertSame([], $transport->messages);
    }
}
