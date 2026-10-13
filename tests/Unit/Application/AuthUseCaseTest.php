<?php

namespace Tests\Unit\Application;

use App\Application\Errors\InvalidCredentials;
use App\Application\Errors\UsernameTaken;
use App\Application\UseCases\CreateLibrarianService;
use App\Application\UseCases\LoginService;
use App\Domain\User\Role;
use App\Domain\User\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Application\Fakes\FakePasswordHasher;
use Tests\Unit\Application\Fakes\FakeTokenIssuer;
use Tests\Unit\Application\Fakes\InMemoryUserRepository;

class AuthUseCaseTest extends TestCase
{
    private InMemoryUserRepository $users;
    private FakePasswordHasher $hasher;

    protected function setUp(): void
    {
        $this->users = new InMemoryUserRepository();
        $this->hasher = new FakePasswordHasher();

        $this->users->save(User::provision('u-0', 'admin', $this->hasher->hash('clave-admin'), Role::Admin));
    }

    // HU-01

    public function test_valid_credentials_return_a_token_valid_for_8_hours(): void
    {
        $service = new LoginService($this->users, $this->hasher, new FakeTokenIssuer());

        $token = $service->execute('admin', 'clave-admin');

        $this->assertSame(28800, $token->expiresIn);
        $this->assertSame('admin', $token->role);
    }

    public function test_wrong_password_and_unknown_user_give_the_same_error(): void
    {
        $service = new LoginService($this->users, $this->hasher, new FakeTokenIssuer());

        $this->expectException(InvalidCredentials::class);
        $service->execute('admin', 'clave-incorrecta');
    }

    public function test_unknown_user_gives_invalid_credentials(): void
    {
        $service = new LoginService($this->users, $this->hasher, new FakeTokenIssuer());

        $this->expectException(InvalidCredentials::class);
        $service->execute('nadie', 'clave-admin');
    }

    // HU-02

    public function test_admin_creates_a_librarian_with_hashed_password(): void
    {
        $service = new CreateLibrarianService($this->users, $this->hasher);

        $user = $service->execute('u-1', '  Ana ', 'clave-ana', 'librarian');

        $this->assertSame('ana', $user->username());
        $this->assertSame(Role::Librarian, $user->role());
        $this->assertSame('hashed:clave-ana', $user->passwordHash());
        $this->assertNotNull($this->users->findByUsername('ana'));
    }

    public function test_repeated_username_is_rejected_after_normalization(): void
    {
        $service = new CreateLibrarianService($this->users, $this->hasher);
        $service->execute('u-1', 'ana', 'clave-ana', 'librarian');

        $this->expectException(UsernameTaken::class);
        $service->execute('u-2', 'ANA ', 'otra', 'librarian');
    }

    public function test_requesting_admin_role_is_rejected(): void
    {
        $service = new CreateLibrarianService($this->users, $this->hasher);

        $this->expectException(InvalidArgumentException::class);
        $service->execute('u-1', 'ana', 'clave-ana', 'admin');
    }
}
