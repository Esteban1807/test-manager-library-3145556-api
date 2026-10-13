<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract BOOK_HAS_OPEN_LOANS (409), CA-03.5
final class BookHasOpenLoans extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('No se puede dar de baja un libro con préstamos abiertos.');
    }
}
