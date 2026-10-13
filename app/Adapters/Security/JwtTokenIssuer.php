<?php

namespace App\Adapters\Security;

use App\Application\Ports\IssuedToken;
use App\Application\Ports\TokenIssuer;
use App\Domain\User\User;
use Firebase\JWT\JWT;

// HS256 token, 8 hours, with sub and role only: no personal data inside (api-contract §4)
final class JwtTokenIssuer implements TokenIssuer
{
    public function issue(User $user): IssuedToken
    {
        $ttl = (int) config('library.jwt_ttl_seconds');
        $now = time();

        $token = JWT::encode([
            'sub' => $user->id(),
            'role' => $user->role()->value,
            'iat' => $now,
            'exp' => $now + $ttl,
        ], (string) config('library.jwt_secret'), 'HS256');

        return new IssuedToken($token, $ttl, $user->role()->value);
    }
}
