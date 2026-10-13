<?php

namespace App\Application\Ports;

use App\Domain\User\User;

// Port defined by the application (Article II). Implemented by Adapters/Persistence.
interface UserRepository
{
    public function findByUsername(string $username): ?User;

    public function save(User $user): void;
}
