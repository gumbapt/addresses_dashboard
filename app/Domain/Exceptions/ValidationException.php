<?php

namespace App\Domain\Exceptions;

use Exception;

class ValidationException extends Exception
{
    public function __construct(string $message = "Validation failed", int $code = 400, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

