<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract ISBN_TAKEN (409), CA-03.2
final class IsbnTaken extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya existe un libro activo con ese ISBN.');
    }
}
