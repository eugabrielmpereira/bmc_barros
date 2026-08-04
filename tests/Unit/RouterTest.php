<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Router;
use PHPUnit\Framework\TestCase;

final class RouterTest extends TestCase
{
    public function testResolveStaticRoute(): void
    {
        $router = new Router();
        $handler = static fn () => null;
        $router->add('GET', '/admin/dashboard', $handler, 'admin');

        $route = $router->resolve('GET', '/admin/dashboard');

        $this->assertNotNull($route);
        $this->assertSame($handler, $route['handler']);
        $this->assertSame('admin', $route['middleware']);
        $this->assertSame([], $route['params']);
    }

    public function testResolveDynamicRouteParams(): void
    {
        $router = new Router();
        $handler = static fn () => null;
        $router->add('POST', '/admin/produtos/:id/entrada', $handler, 'admin');

        $route = $router->resolve('POST', '/admin/produtos/42/entrada');

        $this->assertNotNull($route);
        $this->assertSame(['id' => '42'], $route['params']);
    }

    public function testResolveReturnsNullForWrongMethod(): void
    {
        $router = new Router();
        $router->add('GET', '/produtos', static fn () => null, 'web');

        $this->assertNull($router->resolve('POST', '/produtos'));
    }
}
