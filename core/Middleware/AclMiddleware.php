<?php

declare(strict_types=1);

namespace Core\Middleware;

use Core\Exceptions\ForbiddenException;
use Core\Request;
use Core\Response;

/**
 * Controle de acesso por role (config/acl.php).
 */
final class AclMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const GUEST_PATHS = [
        '/admin/login',
    ];

    /** @var array<string, list<string>>|null */
    private static ?array $acl = null;

    public function handle(Request $request, callable $next): Response
    {
        if ($this->isGuestRoute($request)) {
            return $next($request);
        }

        if (!$this->isAuthenticated()) {
            return $next($request);
        }

        $role = $this->resolveRole();
        if (!$this->isAllowed($role, $request->uri())) {
            throw new ForbiddenException('Você não tem permissão para acessar este recurso.');
        }

        return $next($request);
    }

    private function isGuestRoute(Request $request): bool
    {
        return in_array($request->uri(), self::GUEST_PATHS, true);
    }

    private function isAuthenticated(): bool
    {
        return isset($_SESSION['admin_id']);
    }

    private function resolveRole(): string
    {
        $role = $_SESSION['admin_role'] ?? 'admin';

        return is_string($role) && $role !== '' ? $role : 'admin';
    }

    private function isAllowed(string $role, string $uri): bool
    {
        $acl = $this->getAcl();
        $patterns = $acl[$role] ?? [];

        if ($patterns === []) {
            return false;
        }

        foreach ($patterns as $pattern) {
            if ($this->pathMatches($pattern, $uri)) {
                return true;
            }
        }

        return false;
    }

    private function pathMatches(string $pattern, string $uri): bool
    {
        if (str_ends_with($pattern, '*')) {
            $prefix = substr($pattern, 0, -1);

            return $uri === rtrim($prefix, '/') || str_starts_with($uri, $prefix);
        }

        return $uri === $pattern;
    }

    /**
     * @return array<string, list<string>>
     */
    private function getAcl(): array
    {
        if (self::$acl === null) {
            $config = require dirname(__DIR__, 2) . '/config/acl.php';
            self::$acl = is_array($config) ? $config : [];
        }

        return self::$acl;
    }
}
