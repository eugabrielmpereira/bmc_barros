<?php

declare(strict_types=1);

namespace Tests\Unit;

use Core\Request;
use PHPUnit\Framework\TestCase;

final class RequestTest extends TestCase
{
    /** @var array<string, mixed> */
    private array $serverBackup = [];

    protected function setUp(): void
    {
        $this->serverBackup = $_SERVER;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->serverBackup;
    }

    public function testCaptureNormalizesDoubleSlashPathToProdutosRoute(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '//produtos';

        $request = Request::capture();

        $this->assertSame('/produtos', $request->uri());
    }

    public function testCaptureKeepsHomeRouteForRoot(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '/';

        $request = Request::capture();

        $this->assertSame('/', $request->uri());
    }

    public function testCaptureNormalizesCartSummaryPath(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = '//carrinho/resumo';

        $request = Request::capture();

        $this->assertSame('/carrinho/resumo', $request->uri());
    }
}
