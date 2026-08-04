<?php

declare(strict_types=1);

namespace Core\Exceptions;

use RuntimeException;

/**
 * Tenant existe no mestre mas não está disponível para servir requests (ex.: suspended).
 */
final class TenantSuspendedException extends RuntimeException
{
}
