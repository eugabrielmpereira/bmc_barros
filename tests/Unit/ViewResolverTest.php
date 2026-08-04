<?php

declare(strict_types=1);

namespace Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ViewResolverTest extends TestCase
{
    public function testResolveErpViewPath(): void
    {
        $path = resolve_view_path('erp::dashboard/index');

        $this->assertSame(ERP_PATH . '/Admin/Views/dashboard/index.php', $path);
    }

    public function testResolveClientViewPath(): void
    {
        $path = resolve_view_path('client::catalog/index');

        $this->assertSame(CLIENTS_PATH . '/Bmc/Site/Views/catalog/index.php', $path);
    }

    public function testResolveSharedErrorViewPath(): void
    {
        $path = resolve_view_path('shared::errors/404');

        $this->assertSame(SHARED_PATH . '/View/errors/404.php', $path);
    }

    public function testUnprefixedViewIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        resolve_view_path('dashboard/index');
    }
}
