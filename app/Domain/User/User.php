<?php

namespace App\Domain\User;

use InvalidArgumentException;

// Internal operator (spec.md HU-01, HU-02). The domain never sees the clear password:
// it receives a hash produced by the PasswordHasher port (Article IX).
final class User
{
    private const USERNAME_MAX = 120;

    private function __construct(
        private readonly string $id,
        private readonly string $username,
        private readonly string $passwordHash,
        private readonly Role $role,
    ) {}

    // Provisioned by the deployment (admin bootstrap, D-10). Any role is allowed here.
    public static function provision(string $id, string $username, string $passwordHash, Role $role): self
    {
        return new self($id, self::normalizeUsername($username), $passwordHash, $role);
    }

    // CA-02.3: over the API, only `librarian` can be requested; `admin` answers 422
    public static function registerFromApi(string $id, string $username, string $passwordHash, string $role): self
    {
        if ($role !== Role::Librarian->value) {
            throw new InvalidArgumentException('Solo se puede dar de alta un bibliotecario.');
        }

        return new self($id, self::normalizeUsername($username), $passwordHash, Role::Librarian);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): Role
    {
        return $this->role;
    }

    // CA-02.2: stored lowercase and trimmed
    private static function normalizeUsername(string $raw): string
    {
        $username = mb_strtolower(trim($raw));

        if ($username === '' || mb_strlen($username) > self::USERNAME_MAX) {
            throw new InvalidArgumentException('El nombre de usuario es obligatorio y no puede superar 120 caracteres.');
        }

        return $username;
    }
}
