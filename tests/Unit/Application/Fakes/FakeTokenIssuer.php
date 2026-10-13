<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\IssuedToken;
use App\Application\Ports\TokenIssuer;
use App\Domain\User\User;

final class FakeTokenIssuer implements TokenIssuer
{
    public function issue(User $user): IssuedToken
    {
        return new IssuedToken('token-'.$user->username(), 28800, $user->role()->value);
    }
}
