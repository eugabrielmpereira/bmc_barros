<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Shared\Support\TaxId;

final class TaxIdTest extends TestCase
{
    public function testValidCpf(): void
    {
        $this->assertTrue(TaxId::isValidDocument('111.444.777-35'));
    }

    public function testInvalidCpfChecksum(): void
    {
        $this->assertFalse(TaxId::isValidDocument('123.456.789-00'));
    }
}
