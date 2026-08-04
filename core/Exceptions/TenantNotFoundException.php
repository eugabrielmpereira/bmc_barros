<?php

declare(strict_types=1);

namespace Core\Exceptions;

use RuntimeException;

/**
 * HTTP Host não mapeado a nenhum tenant (modo domain, fora de fallback dev).
 */
final class TenantNotFoundException extends RuntimeException
{
}
