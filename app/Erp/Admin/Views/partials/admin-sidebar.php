<?php
/** @var string $paginaAtiva */
$paginaAtiva = $paginaAtiva ?? '';
?>
<aside class="admin-sidebar" aria-label="Menu do painel">
    <p><strong>ERP Admin</strong></p>
    <nav>
        <ul>
            <li>
                <a href="<?= url('/admin/dashboard') ?>"<?= $paginaAtiva === 'dashboard' ? ' aria-current="page"' : '' ?>>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/categorias') ?>"<?= $paginaAtiva === 'categorias' ? ' aria-current="page"' : '' ?>>
                    Categorias
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/produtos') ?>"<?= $paginaAtiva === 'produtos' ? ' aria-current="page"' : '' ?>>
                    Produtos
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/estoque') ?>"<?= $paginaAtiva === 'estoque' ? ' aria-current="page"' : '' ?>>
                    Histórico de Estoque
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/inventario-rapido') ?>"<?= $paginaAtiva === 'inventario' ? ' aria-current="page"' : '' ?>>
                    Inventário Rápido
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/usuarios') ?>"<?= $paginaAtiva === 'usuarios' ? ' aria-current="page"' : '' ?>>
                    Usuários
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/clientes') ?>"<?= $paginaAtiva === 'clientes' ? ' aria-current="page"' : '' ?>>
                    Clientes
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/pedidos') ?>"<?= $paginaAtiva === 'pedidos' ? ' aria-current="page"' : '' ?>>
                    Pedidos
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/frete') ?>"<?= $paginaAtiva === 'frete' ? ' aria-current="page"' : '' ?>>
                    Frete e entregas
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/contas-a-receber') ?>"<?= $paginaAtiva === 'contas-a-receber' ? ' aria-current="page"' : '' ?>>
                    Contas a receber
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/relatorios') ?>"<?= $paginaAtiva === 'relatorios' ? ' aria-current="page"' : '' ?>>
                    Relatórios
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/auditoria') ?>"<?= $paginaAtiva === 'auditoria' ? ' aria-current="page"' : '' ?>>
                    Auditoria
                </a>
            </li>
            <li>
                <a href="<?= url('/admin/logout') ?>">Sair</a>
            </li>
        </ul>
    </nav>
</aside>
