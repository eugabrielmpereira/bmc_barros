<?php

declare(strict_types=1);

namespace Core\Exceptions;

class ValidationException extends HttpException
{
    /**
     * @param array<string, list<string>> $errors
     */
    public function __construct(
        string $message = 'Dados inválidos.',
        private array $errors = []
    ) {
        parent::__construct(422, $message, ['errors' => $errors]);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
