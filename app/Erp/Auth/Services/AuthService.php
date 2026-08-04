<?php

declare(strict_types=1);

namespace Erp\Auth\Services;

use Core\Security\LoginRateLimiter;
use Erp\Audit\Services\AuditService;
use Erp\Auth\Repositories\UsuarioRepository;

/**
 * Autenticação do painel admin (sessão compatível com admin_id).
 */
class AuthService
{
    public function __construct(
        private ?LoginRateLimiter $loginRateLimiter = null,
        private ?UsuarioRepository $usuarioRepository = null
    ) {
    }

    /**
     * Tenta autenticar por e-mail e senha em texto plano.
     * Em sucesso: regenera sessão e define $_SESSION['admin_id'] (e nome opcional).
     */
    public function attemptLogin(string $email, string $plainPassword): bool
    {
        $email = trim($email);
        if ($email === '' || $plainPassword === '') {
            return false;
        }

        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'));
        if ($ip === '') {
            $ip = '0.0.0.0';
        }

        $limiter = $this->getLimiter();
        if ($limiter->isBlocked($ip)) {
            return false;
        }

        $row = $this->getUsuarioRepository()->findActiveByEmail($email);

        if ($row === null || !password_verify($plainPassword, (string) ($row['senha'] ?? ''))) {
            $limiter->recordFailure($ip);

            return false;
        }

        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int) $row['id'];
        $_SESSION['admin_nome'] = (string) ($row['nome'] ?? '');
        $_SESSION['admin_role'] = (string) ($row['role'] ?? 'admin');
        $_SESSION['last_activity'] = time();

        $limiter->clearForIp($ip);
        (new AuditService())->record('login', 'user', (int) $row['id'], ['email' => $email]);

        return true;
    }

    /**
     * Encerra sessão e remove cookie de sessão (logout seguro).
     */
    public function logout(): void
    {
        $userId = isset($_SESSION['admin_id']) ? (int) $_SESSION['admin_id'] : null;
        (new AuditService())->record('logout', 'user', $userId);

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'] ?? '',
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    private function getLimiter(): LoginRateLimiter
    {
        return $this->loginRateLimiter ?? new LoginRateLimiter();
    }

    private function getUsuarioRepository(): UsuarioRepository
    {
        return $this->usuarioRepository ?? new UsuarioRepository();
    }
}
