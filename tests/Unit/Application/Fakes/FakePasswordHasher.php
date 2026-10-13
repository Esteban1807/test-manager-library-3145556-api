<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\PasswordHasher;

// Deterministic stand-in for Argon2id: never a real hash, only for tests
final class FakePasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return 'hashed:'.$plainPassword;
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return $this->hash($plainPassword) === $hash;
    }
}
