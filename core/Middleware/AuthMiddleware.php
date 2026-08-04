<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Request;
use Core\Response;

/**
 * Exige sessão autenticada (admin_id) nas rotas do grupo admin.
 * Rotas de convidado (login) ficam liberadas sem sessão.
 */
final class AuthMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const GUEST_PATHS = [
        '/admin/login',
    ];

    public function handle(Request $request, callable $next): Response
    {
        if ($this->isGuestRoute($request) || $this->isAuthenticated()) {
            return $next($request);
        }

        return Response::redirect(BASE_URL . '/admin/login');
    }

    private function isGuestRoute(Request $request): bool
    {
        return in_array($request->uri(), self::GUEST_PATHS, true);
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['admin_id']);
    }
}
