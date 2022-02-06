<?php

namespace Core\Exceptions;

class ValidationException extends \RuntimeException
{
    private array $errors;

    public function __construct(array $errors = [], string $message = '', int $code = 0, \Exception $previous = null)
    {
        $this->errors = $errors;
    
        parent::__construct($message, $code, $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
