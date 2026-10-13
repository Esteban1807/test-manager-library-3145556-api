<?php

namespace App\Application\Ports;

// Login answer (api-contract §3.1). 28800 seconds = 8 hours (CA-01.1).
final readonly class IssuedToken
{
    public function __construct(
        public string $accessToken,
        public int $expiresIn,
        public string $role,
    ) {}
}
