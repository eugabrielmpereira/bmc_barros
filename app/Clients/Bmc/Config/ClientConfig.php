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
            // [REVISAR] Obrigatório no go-live (Decreto 7.962/2013): preencha com o CNPJ real.
            // Enquanto vazio, o rodapé não exibe CNPJ (evita publicar número fictício).
            'cnpj' => (string) ($_ENV['STORE_CNPJ'] ?? ''),
            'tagline' => 'Construção & Mais',
            'adminName' => 'ERP Admin',
            'emailPlaceholder' => 'admin@bmcbarros.com.br',
            'phone' => '(16) 99731-4213',
            'phoneHref' => '+5516997314213',
            'whatsappNumber' => '5516997314213',
            'address' => 'Av. Ver. José Antônio De Lima, 120 - Rincão - SP',
            'city' => 'Rincão',
            'state' => 'SP',
            'serviceArea' => 'Rincão, Taquaral e região',
            'seoDescription' => 'Material de construção em Rincão-SP: cimento, areia, ferragens, hidráulica, elétrica, pisos e tintas. Compre online com entrega em caminhão próprio ou retire na loja.',
            // Fonte Archivo hospedada no próprio site (sem requisição ao Google Fonts).
            'fontsUrl' => '',
            'fontPreload' => '/assets/clients/bmc/site/fonts/archivo-latin-wdth.woff2',
            'businessHours' => [
                'weekday' => '08h às 18h',
                'saturday' => '08h às 17h',
                'sunday' => 'Fechado',
            ],
            'assets' => [
                'base' => '/assets/clients/bmc/site',
                'logo' => '/assets/clients/bmc/site/img/bmc-logo.webp',
                'logoJpg' => '/assets/clients/bmc/site/img/bmc-logo-recorte.jpg',
                'storePhoto' => '/assets/clients/bmc/site/img/bmc-loja-fachada.webp',
                'storePhotoSmall' => '/assets/clients/bmc/site/img/bmc-loja-fachada-800.webp',
                'heroProducts' => '/assets/clients/bmc/site/img/bmc-hero-materials.webp',
                'mapCard' => '/assets/clients/bmc/site/img/bmc-map-card.webp',
                'ogImage' => '/assets/clients/bmc/site/img/bmc-og.jpg',
                'favicon' => '/assets/clients/bmc/site/img/favicon-32.png',
                'appleIcon' => '/assets/clients/bmc/site/img/bmc-icon-180.png',
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
