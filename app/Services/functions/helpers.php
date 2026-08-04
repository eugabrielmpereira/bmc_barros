<?php
// ============================================================
// functions/helpers.php — Funções utilitárias reutilizáveis
// ============================================================

require_once dirname(__DIR__, 2) . '/Helpers/functions.php';

/**
 * Escapa string para exibição segura no HTML.
 * USE SEMPRE que for exibir dado do banco ou do usuário.
 * Previne XSS (Cross-Site Scripting).
 */
function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Formata valor decimal para moeda brasileira.
 * Ex: 1234.5 → "R$ 1.234,50"
 */
function formatarPreco(?float $valor): string
{
    if ($valor === null) return '—';
    return 'R$ ' . number_format($valor, 2, ',', '.');
}

function slugify(string $value): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT', $value) ?: $value;
    $value = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
    $value = trim($value, '-');
    return strtolower($value);
}

/**
 * Faz upload de imagem com validação de segurança.
 * Retorna o nome do arquivo salvo ou null em caso de erro.
 *
 * @param array  $arquivo   $_FILES['imagem']
 * @param string $destino   Pasta de destino (ex: __DIR__ . '/../admin/uploads/')
 * @return string|null
 */
function uploadImagem(array $arquivo, string $destino): ?string
{
    // Tipos MIME permitidos (validados pelo conteúdo real do arquivo, não só extensão)
    $tiposPermitidos = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

    // Tamanho máximo: 3MB
    $tamanhoMaximo = 3 * 1024 * 1024;

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    if ($arquivo['size'] > $tamanhoMaximo) {
        return null; // Arquivo muito grande
    }

    // finfo valida o tipo real do arquivo pelo conteúdo binário
    // (não pela extensão, que o usuário pode falsificar)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeReal = $finfo->file($arquivo['tmp_name']);

    if (!in_array($mimeReal, $tiposPermitidos, true)) {
        return null; // Tipo não permitido
    }

    // Gera nome único para evitar sobrescrita de arquivos
    $extensao    = match ($mimeReal) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    };
    $nomeArquivo = uniqid('img_', true) . '.' . $extensao;
    $caminhoFull = rtrim($destino, '/') . '/' . $nomeArquivo;

    if (!move_uploaded_file($arquivo['tmp_name'], $caminhoFull)) {
        return null; // Falha ao mover o arquivo
    }

    return $nomeArquivo;
}

/**
 * Badge pill de status (ativo/inativo) com ícone.
 */
function badgeStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    $isActive = in_array($normalized, ['ativo', 'active'], true);

    if ($isActive) {
        return '<span class="badge-pill badge-pill-confirmed"><i class="ti ti-check" aria-hidden="true"></i> Ativo</span>';
    }

    return '<span class="badge-pill badge-pill-inactive"><i class="ti ti-ban" aria-hidden="true"></i> Inativo</span>';
}

/**
 * Badge pill de status genérico (clientes, registros).
 */
function badgeRecordStatus(string $status): string
{
    $normalized = strtolower(trim($status));
    $map = [
        'active' => ['class' => 'badge-pill-confirmed', 'icon' => 'ti-check', 'label' => 'Ativo'],
        'ativo' => ['class' => 'badge-pill-confirmed', 'icon' => 'ti-check', 'label' => 'Ativo'],
        'inactive' => ['class' => 'badge-pill-inactive', 'icon' => 'ti-ban', 'label' => 'Inativo'],
        'inativo' => ['class' => 'badge-pill-inactive', 'icon' => 'ti-ban', 'label' => 'Inativo'],
        'draft' => ['class' => 'badge-pill-pending', 'icon' => 'ti-clock', 'label' => 'Pendente'],
        'confirmed' => ['class' => 'badge-pill-confirmed', 'icon' => 'ti-check', 'label' => 'Faturado'],
        'cancelled' => ['class' => 'badge-pill-cancelled', 'icon' => 'ti-x', 'label' => 'Cancelado'],
    ];

    $config = $map[$normalized] ?? ['class' => 'badge-pill-origin', 'icon' => 'ti-info-circle', 'label' => $status];

    return sprintf(
        '<span class="badge-pill %s"><i class="ti %s" aria-hidden="true"></i> %s</span>',
        e($config['class']),
        e($config['icon']),
        e($config['label'])
    );
}

/**
 * Badge de estoque com pill colorido.
 */
function badgeEstoque(int $qtd): string
{
    if ($qtd === 0) {
        return '<span class="badge-pill badge-pill-cancelled"><i class="ti ti-package-off" aria-hidden="true"></i> Sem estoque</span>';
    }
    if ($qtd <= 3) {
        return '<span class="badge-pill badge-pill-pending"><i class="ti ti-alert-triangle" aria-hidden="true"></i> Baixo (' . $qtd . ')</span>';
    }

    return '<span class="badge-pill badge-pill-stock"><i class="ti ti-package" aria-hidden="true"></i> ' . $qtd . ' un.</span>';
}
