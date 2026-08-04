<?php

declare(strict_types=1);

namespace Erp\Finance\Controllers;

use Core\Controller;
use Core\Database;
use Core\Database\TenantWebhookResolver;
use Core\Exceptions\TenantSuspendedException;
use Erp\Finance\Gateways\Asaas\AsaasConfig;
use Erp\Finance\Services\AsaasWebhookService;

final class AsaasWebhookController extends Controller
{
    public function __construct(private ?AsaasWebhookService $webhookService = null)
    {
    }

    public function handle(): void
    {
        $token = $this->extractAccessToken();

        $expectedToken = null;

        if (Database::isMultitenantEnabled()) {
            try {
                $tenantMatch = TenantWebhookResolver::resolveByToken($token);
            } catch (TenantSuspendedException) {
                http_response_code(503);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['message' => 'Serviço temporariamente indisponível.'], JSON_THROW_ON_ERROR);
                exit;
            }

            if ($tenantMatch === null) {
                $this->json(['message' => 'Unauthorized'], 401);

                return;
            }

            Database::initializeForRequest($tenantMatch['client_config_key']);
            $expectedToken = AsaasConfig::webhookToken() !== '' ? AsaasConfig::webhookToken() : null;
            $this->webhookService ??= new AsaasWebhookService();
        } else {
            $this->webhookService ??= new AsaasWebhookService();
        }

        if (!$this->webhookService->isAuthorized($token, $expectedToken)) {
            $this->json(['message' => 'Unauthorized'], 401);

            return;
        }

        $rawBody = file_get_contents('php://input');
        if (!is_string($rawBody) || trim($rawBody) === '') {
            $this->json(['message' => 'Empty body'], 400);

            return;
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($rawBody, true);
        if (!is_array($payload)) {
            $this->json(['message' => 'Invalid JSON'], 400);

            return;
        }

        $result = $this->webhookService->handle($payload);
        $this->json(['message' => $result['message']], (int) $result['http_status']);
    }

    private function extractAccessToken(): ?string
    {
        $token = $_SERVER['HTTP_ASAAS_ACCESS_TOKEN'] ?? null;
        if (is_string($token) && $token !== '') {
            return $token;
        }

        $alt = $_SERVER['HTTP_ASAAS-ACCESS-TOKEN'] ?? '';

        return is_string($alt) && $alt !== '' ? $alt : null;
    }
}
