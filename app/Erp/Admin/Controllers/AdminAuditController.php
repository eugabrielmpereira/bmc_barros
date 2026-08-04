<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Erp\Audit\Services\AuditService;

final class AdminAuditController extends Controller
{
    public function index(): void
    {
        $service = new AuditService();

        $this->view('erp::audit/index', [
            'tituloPagina' => 'Auditoria',
            'paginaAtiva' => 'auditoria',
            'logs' => $service->latest(100),
        ], 'erp::layouts/admin');
    }
}
