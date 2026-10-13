<?php

namespace App\Adapters\Security;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

// Decodes and checks the signature and expiry. Throws on any problem; the middleware turns that into 401.
final class JwtVerifier
{
    public function decode(string $token): stdClass
    {
        return JWT::decode($token, new Key((string) config('library.jwt_secret'), 'HS256'));
    }
}
