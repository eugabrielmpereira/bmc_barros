<?php

declare(strict_types=1);

/**
 * Mapa de tenants para resolução por domínio (Sprint 5 — Fase C).
 *
 * resolution:
 * - env    → usa apenas ACTIVE_CLIENT do .env (comportamento legado)
 * - domain → resolve pelo HTTP Host; fallback para ACTIVE_CLIENT
 */
return [
    'resolution' => strtolower((string) ($_ENV['TENANT_RESOLUTION'] ?? 'env')),

    'default_client' => strtolower((string) ($_ENV['ACTIVE_CLIENT'] ?? 'bmc')),

    'known_clients' => ['bmc', 'demo'],

    /**
     * Host (sem porta, minúsculo) => client key.
     * Ex.: vhosts locais ou domínios de produção por tenant.
     */
    'hosts' => [
        'localhost' => 'bmc',
        '127.0.0.1' => 'bmc',
        'bmc.local' => 'bmc',
        'demo.local' => 'demo',
    ],

    /**
     * BASE_URL fixa por tenant (opcional).
     * Se vazio, em modo domain a URL é derivada da requisição HTTP.
     */
    'base_urls' => [
        'bmc' => 'http://bmc.local',
        'demo' => 'http://demo.local',
        // Produção: troque pelos domínios reais sem barra no final.
    ],
];
