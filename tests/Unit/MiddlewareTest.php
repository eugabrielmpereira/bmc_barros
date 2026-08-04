<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Exceptions\ForbiddenException;
use Core\Middleware\AclMiddleware;
use Core\Middleware\AuthMiddleware;
use Core\Request;
use Core\Response;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class MiddlewareTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
    }

    public function testAuthAllowsGuestLoginRoute(): void
    {
        $middleware = new AuthMiddleware();
        $request = new Request('GET', '/admin/login');

        $response = $middleware->handle($request, fn (): Response => Response::html('login'));

        $this->assertSame(200, $this->responseStatus($response));
        $this->assertSame('login', $this->responseBody($response));
    }

    public function testAuthRedirectsUnauthenticatedAdminRoute(): void
    {
        $middleware = new AuthMiddleware();
        $request = new Request('GET', '/admin/dashboard');

        $response = $middleware->handle($request, fn (): Response => Response::html('dashboard'));

        $this->assertSame(302, $this->responseStatus($response));
        $this->assertSame(BASE_URL . '/admin/login', $this->responseHeaders($response)['Location']);
    }

    public function testAclBlocksConfiguredRoleWithoutPermission(): void
    {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_role'] = 'admin';

        $middleware = new AclMiddleware();
        $request = new Request('GET', '/admin/configuracoes');

        $this->expectException(ForbiddenException::class);

        $middleware->handle($request, fn (): Response => Response::html('users'));
    }

    public function testAclAllowsConfiguredAdminRoute(): void
    {
        $_SESSION['admin_id'] = 1;
        $_SESSION['admin_role'] = 'admin';

        $middleware = new AclMiddleware();
        $request = new Request('GET', '/admin/produtos/editar/1');

        $response = $middleware->handle($request, fn (): Response => Response::html('ok'));

        $this->assertSame('ok', $this->responseBody($response));
    }

    private function responseStatus(Response $response): int
    {
        return $this->readResponseProperty($response, 'statusCode');
    }

    /**
     * @return array<string, string>
     */
    private function responseHeaders(Response $response): array
    {
        return $this->readResponseProperty($response, 'headers');
    }

    private function responseBody(Response $response): string
    {
        return $this->readResponseProperty($response, 'body');
    }

    private function readResponseProperty(Response $response, string $property): mixed
    {
        $reflection = new ReflectionClass($response);
        $prop = $reflection->getProperty($property);
        $prop->setAccessible(true);

        return $prop->getValue($response);
    }
}
