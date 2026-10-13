<?php

namespace App\Adapters\Persistence;

use App\Adapters\Persistence\Models\UserRecord;
use App\Application\Ports\UserRepository;
use App\Domain\User\Role;
use App\Domain\User\User;

final class EloquentUserRepository implements UserRepository
{
    public function findByUsername(string $username): ?User
    {
        $record = UserRecord::query()->where('username', $username)->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function save(User $user): void
    {
        UserRecord::query()->updateOrCreate(['id' => $user->id()], [
            'username' => $user->username(),
            'password_hash' => $user->passwordHash(),
            'role' => $user->role()->value,
        ]);
    }

    private function toDomain(UserRecord $record): User
    {
        return User::reconstitute($record->id, $record->username, $record->password_hash, Role::from($record->role));
    }
}
