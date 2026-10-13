<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract NOT_FOUND (404): missing or deleted (CA-04.3)
final class BookNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El libro no existe o fue dado de baja.');
    }
}
