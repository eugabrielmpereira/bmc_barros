<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Exige sessão de cliente final (user_id) nas rotas protegidas do site.
 */
final class CustomerAuthMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const GUEST_PATHS = [
        '/entrar',
        '/cadastro',
    ];

    public function handle(Request $request, callable $next): Response
    {
        if ($this->isGuestRoute($request) || $this->isAuthenticated()) {
            return $next($request);
        }

        $redirect = $request->uri();
        $query = trim((string) ($_SERVER['QUERY_STRING'] ?? ''));
        if ($query !== '') {
            $redirect .= '?' . $query;
        }

        $loginUrl = BASE_URL . '/entrar';
        if ($redirect !== '' && $redirect !== '/entrar') {
            $loginUrl .= '?redirect=' . rawurlencode($redirect);
        }

        return Response::redirect($loginUrl);
    }

    private function isGuestRoute(Request $request): bool
    {
        return in_array($request->uri(), self::GUEST_PATHS, true);
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }
}
