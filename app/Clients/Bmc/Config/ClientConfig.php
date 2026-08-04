<?php

declare(strict_types=1);

namespace Clients\Bmc\Config;

final class ClientConfig
{
    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        return [
            'key' => 'bmc',
            'name' => 'BMC Barros',
            'legalName' => 'BMC Barros - Construção & Mais',
            // [REVISAR] Substituir pelo CNPJ real da loja antes do go-live.
            'cnpj' => '00.000.000/0000-00',
            'tagline' => 'Construção & Mais',
            'adminName' => 'ERP Admin',
            'emailPlaceholder' => 'admin@bmcbarros.com.br',
            'phone' => '(16) 99731-4213',
            'phoneHref' => '+5516997314213',
            'whatsappNumber' => '5516997314213',
            'address' => 'Av. Ver. José Antônio De Lima, 120 - Rincão - SP',
            'businessHours' => [
                'weekday' => '08h às 18h',
                'saturday' => '08h às 17h',
                'sunday' => 'Fechado',
            ],
            'assets' => [
                'base' => '/assets/clients/bmc/site',
                'logo' => '/assets/clients/bmc/site/img/bmc-logo-recorte.jpg',
                'storePhoto' => '/assets/clients/bmc/site/img/bmc-loja-fachada.jpg',
                'heroProducts' => '/assets/clients/bmc/site/img/bmc-hero-materials.png',
                'mapCard' => '/assets/clients/bmc/site/img/bmc-map-card.png',
            ],
            'uploads' => [
                'products' => '/uploads/clients/bmc/products',
            ],
            'commerce' => [
                'orderReferencePrefix' => 'BMC',
                'strictOwnership' => true,
            ],
            'legalPages' => [
                'privacy' => '/politica-de-privacidade',
                'terms' => '/termos-de-uso',
            ],
        ];
    }
}
