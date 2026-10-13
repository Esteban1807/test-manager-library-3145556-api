<?php

namespace App\Adapters\Security;

use App\Application\Ports\PasswordHasher;
use Illuminate\Support\Facades\Hash;

// Argon2id behind the PasswordHasher port (plan D-09, Article IX)
final class LaravelPasswordHasher implements PasswordHasher
{
    public function hash(string $plainPassword): string
    {
        return Hash::driver('argon2id')->make($plainPassword);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return Hash::driver('argon2id')->check($plainPassword, $hash);
    }
}
