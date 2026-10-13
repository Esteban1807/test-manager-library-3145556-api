<?php

namespace App\Adapters\Http\Errors;

use RuntimeException;

// Role not allowed for this endpoint (api-contract FORBIDDEN_ROLE, 403; CA-01.3)
final class ForbiddenRole extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Tu rol no permite esta operación.');
    }
}
