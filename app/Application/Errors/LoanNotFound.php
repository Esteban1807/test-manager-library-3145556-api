<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract NOT_FOUND (404) for a loan or one of its lines
final class LoanNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El préstamo o la línea no existen.');
    }
}
