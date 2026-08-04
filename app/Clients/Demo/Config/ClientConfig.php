<?php

declare(strict_types=1);

namespace Clients\Demo\Config;

final class ClientConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return [
            'key' => 'demo',
            'name' => 'Cliente Demonstração',
            'legalName' => 'Cliente Demonstração LTDA',
            'tagline' => 'Site público demonstrativo',
            'adminName' => 'ERP Admin',
            'emailPlaceholder' => 'admin@example.com',
            'phone' => '(00) 00000-0000',
            'phoneHref' => '+5500000000000',
            'whatsappNumber' => '5500000000000',
            'address' => 'Endereço demonstrativo',
            'businessHours' => [
                'weekday' => '08h às 18h',
                'saturday' => '08h às 12h',
                'sunday' => 'Fechado',
            ],
            'assets' => [
                'base' => '/assets/clients/demo/site',
                'logo' => '/assets/clients/demo/site/img/logo-placeholder.svg',
            ],
            'uploads' => [
                'products' => '/uploads/clients/demo/products',
            ],
            'commerce' => [
                'orderReferencePrefix' => 'DEMO',
                'strictOwnership' => true,
            ],
        ];
    }
}
