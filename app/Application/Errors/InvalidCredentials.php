<?php

namespace App\Application\Errors;

use RuntimeException;

// CA-01.2: same answer whether the user or the password is wrong (api-contract INVALID_CREDENTIALS, 401)
final class InvalidCredentials extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Usuario o contraseña incorrectos.');
    }
}
