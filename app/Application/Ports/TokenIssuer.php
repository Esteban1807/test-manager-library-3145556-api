<?php

namespace App\Application\Ports;

use App\Domain\User\User;

// Port: token generation lives behind this interface (plan D-11).
interface TokenIssuer
{
    public function issue(User $user): IssuedToken;
}
