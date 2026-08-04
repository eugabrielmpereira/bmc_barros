<?php

declare(strict_types=1);

namespace Core\Exceptions;

class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Página não encontrada.', array $context = [])
    {
        parent::__construct(404, $message, $context);
    }
}
