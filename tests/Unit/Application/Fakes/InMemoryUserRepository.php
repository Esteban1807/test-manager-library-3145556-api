<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\UserRepository;
use App\Domain\User\User;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    public array $byUsername = [];

    public function findByUsername(string $username): ?User
    {
        return $this->byUsername[$username] ?? null;
    }

    public function save(User $user): void
    {
        $this->byUsername[$user->username()] = $user;
    }
}
