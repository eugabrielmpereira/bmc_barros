<?php

declare(strict_types=1);

namespace Clients\Acme2\Config;

final class ClientConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return [
            'key' => 'acme2',
            'name' => 'Acme Two Store',
            'legalName' => 'Acme Two Store',
            // [REVISAR] Substituir pelo CNPJ real da loja antes do go-live.
            'cnpj' => '[REVISAR]',
            'tagline' => 'Loja online',
            'adminName' => 'ERP Admin',
            'emailPlaceholder' => 'admin@example.com',
            'phone' => '[REVISAR]',
            'phoneHref' => '+5500000000000',
            'whatsappNumber' => '5500000000000',
            // [REVISAR] Endereço comercial completo.
            'address' => '[REVISAR]',
            'businessHours' => [
                'weekday' => '08h às 18h',
                'saturday' => '08h às 12h',
                'sunday' => 'Fechado',
            ],
            'assets' => [
                'base' => '/assets/clients/acme2/site',
                'logo' => '/assets/clients/acme2/site/img/logo-placeholder.svg',
            ],
            'uploads' => [
                'products' => '/uploads/clients/acme2/products',
            ],
            'commerce' => [
                'orderReferencePrefix' => 'ACME2',
                'strictOwnership' => true,
            ],
            'legalPages' => [
                'privacy' => '/politica-de-privacidade',
                'terms' => '/termos-de-uso',
            ],
        ];
    }
}
