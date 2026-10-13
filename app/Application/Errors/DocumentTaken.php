<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract DOCUMENT_TAKEN (409), CA-05.1
final class DocumentTaken extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya existe un socio con ese documento.');
    }
}
