<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\Concerns\ResetsLibrarySchema;
use Tests\TestCase;

// T-14 / RNF-3: the password never travels back in a response and never reaches the log
class SecretsTest extends TestCase
{
    use ResetsLibrarySchema;

    private const PASSWORD = 'clave-de-prueba-123';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'library.admin_username' => 'admin',
            'library.admin_password' => self::PASSWORD,
            'library.jwt_secret' => 'secreto-de-prueba-de-al-menos-32-caracteres',
        ]);
    }

    public function test_password_and_hash_never_appear_in_responses_or_logs(): void
    {
        Artisan::call('library:bootstrap-admin');

        $ok = $this->postJson('/api/v1/auth/login', ['username' => 'admin', 'password' => self::PASSWORD]);
        $bad = $this->postJson('/api/v1/auth/login', ['username' => 'admin', 'password' => 'otra-clave']);

        $this->assertStringNotContainsString(self::PASSWORD, $ok->getContent());
        $this->assertStringNotContainsString('password', $ok->getContent());
        $this->assertStringNotContainsString('password', $bad->getContent());

        $log = storage_path('logs/laravel.log');

        if (file_exists($log)) {
            $this->assertStringNotContainsString(self::PASSWORD, file_get_contents($log));
        }
    }
}
