<?php

namespace App\Application\Errors;

use RuntimeException;

// api-contract NOT_FOUND (404) for a member
final class MemberNotFound extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('El socio no existe.');
    }
}
