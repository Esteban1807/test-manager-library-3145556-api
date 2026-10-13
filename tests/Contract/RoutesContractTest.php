<?php

namespace Tests\Contract;

use Firebase\JWT\JWT;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\ResetsLibrarySchema;
use Tests\TestCase;

// T-16: every protected route of api-contract §3 requires a token, and roles are enforced
class RoutesContractTest extends TestCase
{
    use ResetsLibrarySchema;

    private const ID = '00000000-0000-4000-8000-000000000000';

    protected function setUp(): void
    {
        parent::setUp();

        config(['library.jwt_secret' => 'secreto-de-prueba-de-al-menos-32-caracteres']);
    }

    /** @return array<string, array{string, string}> */
    public static function protectedRoutes(): array
    {
        return [
            'categories' => ['GET', '/api/v1/categories'],
            'books search' => ['GET', '/api/v1/books'],
            'book detail' => ['GET', '/api/v1/books/'.self::ID],
            'book create' => ['POST', '/api/v1/books'],
            'book update' => ['PUT', '/api/v1/books/'.self::ID],
            'book delete' => ['DELETE', '/api/v1/books/'.self::ID],
            'cover upload' => ['PUT', '/api/v1/books/'.self::ID.'/cover'],
            'cover read' => ['GET', '/api/v1/books/'.self::ID.'/cover'],
            'cover remove' => ['DELETE', '/api/v1/books/'.self::ID.'/cover'],
            'user create' => ['POST', '/api/v1/users'],
            'members list' => ['GET', '/api/v1/members'],
            'member create' => ['POST', '/api/v1/members'],
            'member detail' => ['GET', '/api/v1/members/'.self::ID],
            'loan create' => ['POST', '/api/v1/loans'],
            'loans list' => ['GET', '/api/v1/loans'],
            'overdue list' => ['GET', '/api/v1/loans/overdue'],
            'loan detail' => ['GET', '/api/v1/loans/'.self::ID],
            'loan return' => ['POST', '/api/v1/loans/'.self::ID.'/items/'.self::ID.'/return'],
            'most borrowed report' => ['GET', '/api/v1/reports/most-borrowed'],
        ];
    }

    #[DataProvider('protectedRoutes')]
    public function test_every_protected_route_requires_a_token(string $method, string $path): void
    {
        $this->json($method, $path)
            ->assertStatus(401)
            ->assertJsonPath('code', 'UNAUTHENTICATED');
    }

    public function test_librarian_cannot_create_users(): void
    {
        $this->postJson('/api/v1/users', ['username' => 'x', 'password' => 'y', 'role' => 'librarian'], $this->tokenFor('librarian'))
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN_ROLE');
    }

    public function test_librarian_cannot_read_the_report(): void
    {
        $this->getJson('/api/v1/reports/most-borrowed?from=2026-10-01&to=2026-10-31', $this->tokenFor('librarian'))
            ->assertStatus(403)
            ->assertJsonPath('code', 'FORBIDDEN_ROLE');
    }

    private function tokenFor(string $role): array
    {
        $token = JWT::encode([
            'sub' => '00000000-0000-4000-8000-000000000001',
            'role' => $role,
            'iat' => time(),
            'exp' => time() + 3600,
        ], config('library.jwt_secret'), 'HS256');

        return ['Authorization' => 'Bearer '.$token];
    }
}
