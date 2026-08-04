<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Erp\Reports\Services\ReportService;

final class AdminReportController extends Controller
{
    public function index(): void
    {
        $service = new ReportService();
        $summary = $service->operationalSummary(
            isset($_GET['start_date']) ? (string) $_GET['start_date'] : null,
            isset($_GET['end_date']) ? (string) $_GET['end_date'] : null
        );

        if ((string) ($_GET['export'] ?? '') === 'csv') {
            $this->exportCsv($summary, (string) ($_GET['type'] ?? 'orders'));

            return;
        }

        $this->view('erp::reports/index', array_merge([
            'tituloPagina' => 'Relatórios',
            'paginaAtiva' => 'relatorios',
        ], $summary), 'erp::layouts/admin');
    }

    /**
     * @param array<string, mixed> $summary
     */
    private function exportCsv(array $summary, string $type): void
    {
        $period = $summary['period'] ?? [];
        $start = (string) ($period['startDate'] ?? date('Y-m-01'));
        $end = (string) ($period['endDate'] ?? date('Y-m-d'));
        $filename = $type === 'stock' ? 'movimentacoes-estoque' : 'pedidos';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '-' . $start . '-a-' . $end . '.csv"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            return;
        }

        fwrite($output, "\xEF\xBB\xBF");

        if ($type === 'stock') {
            fputcsv($output, ['ID', 'Produto', 'Tipo', 'Quantidade', 'Motivo', 'Data'], ';');
            foreach (($summary['stockMovements'] ?? []) as $movement) {
                fputcsv($output, [
                    (string) ($movement['movimento_id'] ?? ''),
                    (string) ($movement['produto_nome'] ?? ''),
                    (string) ($movement['tipo'] ?? ''),
                    (string) ($movement['quantidade'] ?? ''),
                    (string) ($movement['motivo'] ?? ''),
                    (string) ($movement['created_at'] ?? ''),
                ], ';');
            }
        } else {
            fputcsv($output, ['ID', 'Cliente', 'Status', 'Total', 'Data'], ';');
            foreach (($summary['orderRows'] ?? []) as $order) {
                fputcsv($output, [
                    (string) ($order['id'] ?? ''),
                    (string) ($order['client_name'] ?? ''),
                    (string) ($order['status'] ?? ''),
                    number_format((float) ($order['total_amount'] ?? 0), 2, ',', '.'),
                    (string) ($order['created_at'] ?? ''),
                ], ';');
            }
        }

        fclose($output);
    }
}
