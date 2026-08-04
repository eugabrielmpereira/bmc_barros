<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

final class AppUrlTest extends TestCase
{
    public function testAppUrlAvoidsDoubleSlash(): void
    {
        $this->assertSame('http://localhost/bmc_barros/public/produtos', app_url('/produtos'));
    }

    public function testAppUrlKeepsBaseWithoutTrailingSlash(): void
    {
        $this->assertSame('http://localhost/bmc_barros/public', app_url());
    }

    public function testAppUrlSupportsQueryString(): void
    {
        $this->assertSame(
            'http://localhost/bmc_barros/public/produtos?categoria=construcao',
            app_url('/produtos?categoria=construcao')
        );
    }
}
