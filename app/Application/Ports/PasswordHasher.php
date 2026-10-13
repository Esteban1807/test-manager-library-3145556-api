<?php

namespace App\Application\Ports;

// Port: hashing lives behind this interface (plan D-09, Article IX).
interface PasswordHasher
{
    public function hash(string $plainPassword): string;

    public function verify(string $plainPassword, string $hash): bool;
}
