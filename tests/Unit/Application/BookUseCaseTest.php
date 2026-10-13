<?php

namespace Tests\Unit\Application;

use App\Application\Errors\BookHasOpenLoans;
use App\Application\Errors\BookNotFound;
use App\Application\Errors\InvalidCover;
use App\Application\Errors\IsbnTaken;
use App\Application\UseCases\CreateBookService;
use App\Application\UseCases\DeleteBookService;
use App\Application\UseCases\RemoveCoverService;
use App\Application\UseCases\SearchBooksService;
use App\Application\UseCases\UpdateBookService;
use App\Application\UseCases\UploadCoverService;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Application\Fakes\FakeCoverStorage;
use Tests\Unit\Application\Fakes\FakeOpenLoansReader;
use Tests\Unit\Application\Fakes\FixedClock;
use Tests\Unit\Application\Fakes\InMemoryBookRepository;

class BookUseCaseTest extends TestCase
{
    private const NOVELA = '11111111-1111-4111-8111-111111111111';
    private const CIENCIA = '22222222-2222-4222-8222-222222222222';
    private const ISBN = '9780307474728';

    private InMemoryBookRepository $books;
    private FakeOpenLoansReader $openLoans;
    private FakeCoverStorage $storage;
    private FixedClock $clock;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->openLoans = new FakeOpenLoansReader();
        $this->storage = new FakeCoverStorage();
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z'));
    }

    private function create(string $id = 'b-1', string $isbn = self::ISBN, string $title = 'Cien años'): void
    {
        (new CreateBookService($this->books))->execute($id, $title, 'Autor', $isbn, self::NOVELA, 3);
    }

    // HU-03

    public function test_new_book_has_available_equal_to_total(): void
    {
        $this->create();

        $this->assertSame(3, $this->books->findById('b-1')->available());
    }

    public function test_repeated_active_isbn_is_rejected(): void
    {
        $this->create('b-1');

        $this->expectException(IsbnTaken::class);
        $this->create('b-2');
    }

    public function test_isbn_must_have_13_digits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->create('b-1', '12345');
    }

    public function test_update_replaces_fields_and_moves_available_with_total(): void
    {
        $this->create();

        $book = (new UpdateBookService($this->books))->execute('b-1', 'Nuevo título', 'Otro', self::CIENCIA, 5);

        $this->assertSame('Nuevo título', $book->title());
        $this->assertSame(self::CIENCIA, $book->categoryId());
        $this->assertSame(5, $book->available());
    }

    public function test_delete_is_refused_while_book_has_open_loans(): void
    {
        $this->create();
        $this->openLoans->bookIdsWithOpenLoans = ['b-1'];

        $this->expectException(BookHasOpenLoans::class);
        (new DeleteBookService($this->books, $this->openLoans, $this->clock))->execute('b-1');
    }

    public function test_deleted_book_disappears_from_search_and_detail_gives_not_found(): void
    {
        $this->create();
        (new DeleteBookService($this->books, $this->openLoans, $this->clock))->execute('b-1');

        $result = (new SearchBooksService($this->books))->execute(null, null, 1, 20);

        $this->assertSame(0, $result->total);
        $this->expectException(BookNotFound::class);
        (new UpdateBookService($this->books))->execute('b-1', 'x', 'Autor', self::NOVELA, 3);
    }

    // HU-04

    public function test_search_filters_by_text_and_category_and_reports_total(): void
    {
        $this->create('b-1', self::ISBN, 'Cien años de soledad');
        $this->create('b-2', '9780307474729', 'Otro libro');
        (new UpdateBookService($this->books))->execute('b-2', 'Otro libro', 'Autor', self::CIENCIA, 3);

        $result = (new SearchBooksService($this->books))->execute('cien', self::NOVELA, 1, 20);

        $this->assertSame(1, $result->total);
        $this->assertSame('b-1', $result->items[0]->id());
    }

    public function test_page_size_above_50_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SearchBooksService($this->books))->execute(null, null, 1, 51);
    }

    // CA-03.4 portada

    public function test_jpeg_cover_is_stored_and_only_its_key_is_kept(): void
    {
        $this->create();

        $book = (new UploadCoverService($this->books, $this->storage))->execute('b-1', 'bytes', 'image/jpeg');

        $this->assertNotNull($book->coverKey());
        $this->assertSame('bytes', $this->storage->stored[$book->coverKey()]);
    }

    public function test_cover_over_2_mb_is_rejected(): void
    {
        $this->create();

        $this->expectException(InvalidCover::class);
        (new UploadCoverService($this->books, $this->storage))->execute('b-1', str_repeat('a', 3 * 1024 * 1024), 'image/png');
    }

    public function test_cover_must_be_jpeg_or_png(): void
    {
        $this->create();

        $this->expectException(InvalidCover::class);
        (new UploadCoverService($this->books, $this->storage))->execute('b-1', 'bytes', 'image/gif');
    }

    public function test_removing_cover_clears_key_and_deletes_binary(): void
    {
        $this->create();
        $uploaded = (new UploadCoverService($this->books, $this->storage))->execute('b-1', 'bytes', 'image/png');
        $key = $uploaded->coverKey();

        $book = (new RemoveCoverService($this->books, $this->storage))->execute('b-1');

        $this->assertNull($book->coverKey());
        $this->assertArrayNotHasKey($key, $this->storage->stored);
    }
}
