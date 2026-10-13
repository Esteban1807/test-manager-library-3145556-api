<?php

namespace App\Application\Errors;

use RuntimeException;

// CA-02.2: usernames are unique (api-contract USERNAME_TAKEN, 409)
final class UsernameTaken extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Ya existe un usuario con ese nombre.');
    }
}
