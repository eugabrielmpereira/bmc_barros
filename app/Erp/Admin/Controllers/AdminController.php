<?php

declare(strict_types=1);

namespace Erp\Admin\Controllers;

use Core\Controller;
use Core\Security\Csrf;
use Erp\Auth\Services\AuthService;
use Erp\Dashboard\Services\DashboardService;

class AdminController extends Controller
{
    public function login(): void
    {
        if (isset($_SESSION['admin_id'])) {
            $this->redirect('/admin/dashboard');
        }
        $erro = '';
        if (isset($_GET['expirado'])) {
            $erro = 'Sessão expirada. Faça login novamente.';
        } elseif (isset($_GET['erro'])) {
            $erro = 'E-mail ou senha incorretos.';
        }
        $this->view('erp::auth/login', ['erro' => $erro, 'client' => client_config()]);
    }

    public function authenticate(): void
    {
        Csrf::requireValidPostOrAbort();

        $email = trim((string) ($_POST['email'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');

        $auth = new AuthService();
        if ($auth->attemptLogin($email, $senha)) {
            $this->redirect('/admin/dashboard');
        }

        $this->redirect('/admin/login?erro=1');
    }

    public function dashboard(): void
    {
        $dashboard = new DashboardService();
        $dados = $dashboard->getSummary();

        $this->view('erp::dashboard/index', array_merge([
            'tituloPagina' => 'Dashboard',
            'paginaAtiva' => 'dashboard',
        ], $dados), 'erp::layouts/admin');
    }

    public function logout(): void
    {
        $auth = new AuthService();
        $auth->logout();
        $this->redirect('/admin/login');
    }
}
