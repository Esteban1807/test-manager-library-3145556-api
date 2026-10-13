<?php

namespace App\Adapters\Http\Errors;

use RuntimeException;

// Missing, invalid or expired token (api-contract UNAUTHENTICATED, 401; decision D-API-01)
final class Unauthenticated extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Necesitas iniciar sesión.');
    }
}
