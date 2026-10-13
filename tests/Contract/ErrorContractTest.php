<?php

namespace Tests\Contract;

use Tests\Concerns\ResetsLibrarySchema;
use Tests\TestCase;

// T-16: every error answers with the single body of api-contract §1
class ErrorContractTest extends TestCase
{
    use ResetsLibrarySchema;

    protected function setUp(): void
    {
        parent::setUp();

        config(['library.jwt_secret' => 'secreto-de-prueba-de-al-menos-32-caracteres']);
    }

    private function assertErrorBody($response, string $code): void
    {
        $response->assertJsonStructure(['code', 'message', 'details'])
            ->assertJsonPath('code', $code);
    }

    public function test_missing_token_answers_401_unauthenticated(): void
    {
        $this->getJson('/api/v1/books')->assertStatus(401);
        $this->assertErrorBody($this->getJson('/api/v1/books'), 'UNAUTHENTICATED');
    }

    public function test_wrong_credentials_answer_401_invalid_credentials(): void
    {
        $this->assertErrorBody(
            $this->postJson('/api/v1/auth/login', ['username' => 'nadie', 'password' => 'x']),
            'INVALID_CREDENTIALS',
        );
    }

    public function test_missing_fields_answer_422_validation_error_with_details(): void
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422);
        $this->assertErrorBody($response, 'VALIDATION_ERROR');
        $this->assertNotEmpty($response->json('details'));
    }

    public function test_unknown_book_answers_404_not_found(): void
    {
        $this->actingAsToken();

        $this->assertErrorBody(
            $this->getJson('/api/v1/books/00000000-0000-4000-8000-000000000000', $this->auth()),
            'NOT_FOUND',
        );
    }

    public function test_health_is_public_and_answers_ok(): void
    {
        $this->getJson('/api/v1/health')->assertOk()->assertExactJson(['status' => 'ok']);
    }

    private function actingAsToken(): void
    {
        // A signed token for a librarian; no user row is needed to pass the token check
        $this->token = \Firebase\JWT\JWT::encode([
            'sub' => '00000000-0000-4000-8000-000000000001',
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + 3600,
        ], config('library.jwt_secret'), 'HS256');
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer '.$this->token];
    }

    private string $token = '';
}
