<?php

declare(strict_types=1);

namespace Core\Exceptions;

class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Acesso negado.', array $context = [])
    {
        parent::__construct(403, $message, $context);
    }
}
