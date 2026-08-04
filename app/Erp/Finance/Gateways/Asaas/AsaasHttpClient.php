<?php

declare(strict_types=1);

namespace Erp\Finance\Gateways\Asaas;

/**
 * Cliente HTTP mínimo para a API Asaas v3 (sem dependências externas).
 */
final class AsaasHttpClient
{
    public function __construct(
        private ?string $apiKey = null,
        private ?string $baseUrl = null,
        private ?string $userAgent = null
    ) {
        $this->apiKey ??= AsaasConfig::apiKey();
        $this->baseUrl ??= AsaasConfig::baseUrl();
        $this->userAgent ??= AsaasConfig::userAgent();
    }

    /**
     * @param array<string, mixed> $query
     * @return array{success: bool, status: int, data: array<string, mixed>|list<mixed>, message: string}
     */
    public function get(string $path, array $query = []): array
    {
        $url = $this->baseUrl . $path;
        if ($query !== []) {
            $url .= '?' . http_build_query($query);
        }

        return $this->request('GET', $url, null);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{success: bool, status: int, data: array<string, mixed>|list<mixed>, message: string}
     */
    public function post(string $path, array $payload): array
    {
        return $this->request('POST', $this->baseUrl . $path, $payload);
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array{success: bool, status: int, data: array<string, mixed>|list<mixed>, message: string}
     */
    private function request(string $method, string $url, ?array $payload): array
    {
        if ($this->apiKey === '') {
            return [
                'success' => false,
                'status' => 0,
                'data' => [],
                'message' => 'Chave API Asaas não configurada.',
            ];
        }

        $headers = [
            'access_token: ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: ' . $this->userAgent,
            'Accept: application/json',
        ];

        $ch = curl_init($url);
        if ($ch === false) {
            return [
                'success' => false,
                'status' => 0,
                'data' => [],
                'message' => 'Falha ao iniciar requisição HTTP.',
            ];
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
        ]);

        if ($payload !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        }

        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            error_log('[asaas_http] curl error: ' . $curlError);

            return [
                'success' => false,
                'status' => $status,
                'data' => [],
                'message' => 'Erro de comunicação com o gateway de pagamento.',
            ];
        }

        /** @var array<string, mixed>|list<mixed> $decoded */
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return [
                'success' => false,
                'status' => $status,
                'data' => [],
                'message' => 'Resposta inválida do gateway de pagamento.',
            ];
        }

        if ($status < 200 || $status >= 300) {
            $message = $this->extractErrorMessage($decoded);

            return [
                'success' => false,
                'status' => $status,
                'data' => $decoded,
                'message' => $message,
            ];
        }

        return [
            'success' => true,
            'status' => $status,
            'data' => $decoded,
            'message' => 'OK',
        ];
    }

    /**
     * @param array<string, mixed>|list<mixed> $decoded
     */
    private function extractErrorMessage(array $decoded): string
    {
        if (isset($decoded['errors']) && is_array($decoded['errors'])) {
            $messages = [];
            foreach ($decoded['errors'] as $error) {
                if (is_array($error) && isset($error['description']) && is_string($error['description'])) {
                    $messages[] = $error['description'];
                }
            }
            if ($messages !== []) {
                return implode(' ', $messages);
            }
        }

        if (isset($decoded['message']) && is_string($decoded['message'])) {
            return $decoded['message'];
        }

        return 'Gateway recusou a operação.';
    }
}
