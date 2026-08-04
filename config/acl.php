<?php

declare(strict_types=1);

/**
 * Permissões por role (padrões com * no final = prefixo).
 * Rotas não listadas para a role → 403.
 */
return [
    'admin' => [
        '/admin/dashboard',
        '/admin/estoque',
        '/admin/produtos*',
        '/admin/usuarios*',
        '/admin/clientes*',
        '/admin/pedidos*',
        '/admin/contas-a-receber*',
        '/admin/relatorios',
        '/admin/auditoria',
        '/admin/logout',
        '/admin/categorias*',
        '/admin/usuarios*',
        '/admin/inventario-rapido*',
    ],
    'operator' => [
        '/admin/dashboard',
        '/admin/estoque',
        '/admin/produtos',
        '/admin/clientes',
        '/admin/pedidos*',
        '/admin/contas-a-receber*',
        '/admin/relatorios',
        '/admin/logout',
    ],
];
