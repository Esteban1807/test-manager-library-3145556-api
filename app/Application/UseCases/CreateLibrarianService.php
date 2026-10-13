<?php

namespace App\Application\UseCases;

use App\Application\Errors\UsernameTaken;
use App\Application\Ports\PasswordHasher;
use App\Application\Ports\UserRepository;
use App\Domain\User\User;

// HU-02: an admin creates a librarian (CA-02.1 to CA-02.3)
final class CreateLibrarianService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly PasswordHasher $hasher,
    ) {}

    public function execute(string $id, string $username, string $password, string $role): User
    {
        // Domain validates the role (422) and normalizes the username first
        $user = User::registerFromApi($id, $username, $this->hasher->hash($password), $role);

        if ($this->users->findByUsername($user->username()) !== null) {
            throw new UsernameTaken();
        }

        $this->users->save($user);

        return $user;
    }
}
