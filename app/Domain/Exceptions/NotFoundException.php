<?php

namespace App\Domain\Exceptions;

use Exception;

class NotFoundException extends Exception
{
    public function __construct(string $message = "Resource not found")
    {
        parent::__construct($message);
    }
}

