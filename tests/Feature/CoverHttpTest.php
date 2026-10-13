<?php

namespace Tests\Feature;

use App\Application\UseCases\CreateBookService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\ResetsLibrarySchema;
use Tests\TestCase;

// T-15 over HTTP: 2 MB is accepted, 3 MB is refused with 422, and a missing cover is null
class CoverHttpTest extends TestCase
{
    use ResetsLibrarySchema;

    private const NOVELA = '11111111-1111-4111-8111-111111111111';
    private const BOOK = '00000000-0000-4000-8000-000000000101';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'library.admin_username' => 'admin',
            'library.admin_password' => 'clave-de-prueba-123',
            'library.jwt_secret' => 'secreto-de-prueba-de-al-menos-32-caracteres',
        ]);

        Storage::fake('local');
    }

    private function adminAuth(): array
    {
        Artisan::call('library:bootstrap-admin');

        $token = $this->postJson('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'clave-de-prueba-123',
        ])->json('access_token');

        return ['Authorization' => 'Bearer '.$token];
    }

    // A real 1x1 PNG, padded after its IEND chunk to the requested size.
    // Avoids the GD extension; the padding does not change how the file is detected as PNG.
    private function pngOfKb(int $kb): UploadedFile
    {
        $onePixelPng = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==',
        );

        $path = tempnam(sys_get_temp_dir(), 'png');
        file_put_contents($path, $onePixelPng.str_repeat("\0", $kb * 1024));

        return new UploadedFile($path, 'portada.png', 'image/png', null, true);
    }

    private function createBook(): void
    {
        app(CreateBookService::class)->execute(self::BOOK, 'Título', 'Autor', '9780307474728', self::NOVELA, 2);
    }

    public function test_png_cover_of_2_mb_is_accepted(): void
    {
        $auth = $this->adminAuth();
        $this->createBook();

        $file = $this->pngOfKb(2000);

        $this->put('/api/v1/books/'.self::BOOK.'/cover', ['file' => $file], $auth)
            ->assertOk()
            ->assertJsonPath('id', self::BOOK);

        $this->assertNotNull($this->getJson('/api/v1/books/'.self::BOOK, $auth)->json('cover_key'));
    }

    public function test_cover_of_3_mb_is_refused_with_422(): void
    {
        $auth = $this->adminAuth();
        $this->createBook();

        $file = $this->pngOfKb(3000);

        $this->put('/api/v1/books/'.self::BOOK.'/cover', ['file' => $file], $auth)
            ->assertStatus(422)
            ->assertJsonPath('code', 'VALIDATION_ERROR');
    }

    public function test_book_without_cover_has_null_cover_key(): void
    {
        $auth = $this->adminAuth();
        $this->createBook();

        $this->getJson('/api/v1/books/'.self::BOOK, $auth)
            ->assertOk()
            ->assertJsonPath('cover_key', null);
    }
}
