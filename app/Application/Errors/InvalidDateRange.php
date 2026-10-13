<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract INVALID_DATE_RANGE (422), CA-10.2
final class InvalidDateRange extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('La fecha final no puede ser anterior a la inicial.');
    }
}
