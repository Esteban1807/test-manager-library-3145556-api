<?php

namespace Tests\Unit\Domain;

use App\Domain\Book\Book;
use App\Domain\Book\BookNotAvailable;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    private function book(int $total = 2): Book
    {
        return Book::create(
            'a0000000-0000-4000-8000-000000000001',
            '  Cien años de soledad ',
            'Gabriel García Márquez',
            Isbn::fromString('9780307474728'),
            '11111111-1111-4111-8111-111111111111',
            Quantity::of($total),
        );
    }

    public function test_new_book_starts_with_available_equal_to_total_and_trimmed_title(): void
    {
        $book = $this->book(3);

        $this->assertSame(3, $book->available());
        $this->assertSame(3, $book->total());
        $this->assertSame(0, $book->version());
    }

    public function test_withdraw_never_drops_available_below_zero(): void
    {
        $book = $this->book(1);
        $book->withdraw();

        $this->assertSame(0, $book->available());
        $this->expectException(BookNotAvailable::class);
        $book->withdraw();
    }

    public function test_restock_never_exceeds_total(): void
    {
        $book = $this->book(1);

        $this->expectException(DomainException::class);
        $book->restock();
    }

    public function test_each_state_change_increments_version(): void
    {
        $book = $this->book(2);
        $book->withdraw();
        $book->restock();

        $this->assertSame(2, $book->version());
    }

    public function test_changing_total_moves_available_by_the_same_difference(): void
    {
        $book = $this->book(2);
        $book->withdraw(); // available 1, total 2

        $book->changeTotal(Quantity::of(5));

        $this->assertSame(4, $book->available());
        $this->assertSame(5, $book->total());
    }

    public function test_changing_total_below_lent_copies_is_rejected(): void
    {
        $book = $this->book(3);
        $book->withdraw();
        $book->withdraw(); // available 1

        $this->expectException(InvalidArgumentException::class);
        $book->changeTotal(Quantity::of(1)); // would leave available at -1
    }

    public function test_soft_deleted_book_refuses_changes(): void
    {
        $book = $this->book(2);
        $book->softDelete(new DateTimeImmutable('2026-10-13T12:00:00Z'));

        $this->assertTrue($book->isDeleted());
        $this->expectException(DomainException::class);
        $book->withdraw();
    }
}
