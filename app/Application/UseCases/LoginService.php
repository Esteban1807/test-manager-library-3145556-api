<?php

namespace App\Application\UseCases;

use App\Application\Errors\InvalidCredentials;
use App\Application\Ports\IssuedToken;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\TokenIssuer;
use App\Application\Ports\UserRepository;

// HU-01: authenticate and issue a token (CA-01.1, CA-01.2)
final class LoginService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
        private readonly TokenIssuer $tokens,
    ) {}

    public function execute(string $username, string $password): IssuedToken
    {
        $user = $this->users->findByUsername(mb_strtolower(trim($username)));

        // Same error for unknown user and wrong password (CA-01.2)
        if ($user === null || ! $this->hasher->verify($password, $user->passwordHash())) {
            throw new InvalidCredentials();
        }

        return $this->tokens->issue($user);
    }
}
