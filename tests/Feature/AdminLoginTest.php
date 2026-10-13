<?php

namespace Tests\Feature;

use Tests\Concerns\ResetsLibrarySchema;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

// T-17: the system starts with an empty database and the admin can sign in
class AdminLoginTest extends TestCase
{
    use ResetsLibrarySchema;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'library.admin_username' => 'admin',
            'library.admin_password' => 'clave-de-prueba-123',
            'library.jwt_secret' => 'secreto-de-prueba-de-al-menos-32-caracteres',
        ]);
    }

    public function test_admin_created_from_environment_can_log_in(): void
    {
        Artisan::call('library:bootstrap-admin');

        $response = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'clave-de-prueba-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('expires_in', 28800)
            ->assertJsonPath('role', 'admin');
    }

    public function test_admin_can_use_the_token_on_a_protected_route(): void
    {
        Artisan::call('library:bootstrap-admin');

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'clave-de-prueba-123',
        ])->json('access_token');

        $this->getJson('/api/v1/loans/overdue', ['Authorization' => 'Bearer '.$token])
            ->assertOk()
            ->assertJsonPath('items', []);
    }
}
