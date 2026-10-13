<?php

namespace Tests\Unit\Application;

use App\Application\Errors\InvalidDateRange;
use App\Application\UseCases\MostBorrowedReportService;
use App\Domain\Book\Book;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use App\Domain\Loan\Loan;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Application\Fakes\InMemoryReportReader;

class ReportTest extends TestCase
{
    private const NOVELA = '11111111-1111-4111-8111-111111111111';

    private function lend(array &$loans, string $loanId, Book $book, string $categoryName, string $at): void
    {
        $loan = Loan::open($loanId, 'm-1', 'u-1', new DateTimeImmutable($at));
        $loan->addItem('i-'.$loanId, $book, $categoryName);
        $loans[$loanId] = $loan;
    }

    private function book(string $id, string $title): Book
    {
        return Book::create($id, $title, 'Autor', Isbn::fromString('9780307474728'), self::NOVELA, Quantity::of(10));
    }

    public function test_rows_are_ordered_by_times_borrowed_descending(): void
    {
        $loans = [];
        $a = $this->book('b-a', 'Alfa');
        $b = $this->book('b-b', 'Beta');
        $this->lend($loans, 'L1', $a, 'Novela', '2026-10-02T10:00:00Z');
        $this->lend($loans, 'L2', $b, 'Novela', '2026-10-03T10:00:00Z');
        $this->lend($loans, 'L3', $b, 'Novela', '2026-10-04T10:00:00Z');

        $rows = (new MostBorrowedReportService(new InMemoryReportReader($loans)))
            ->execute(new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-10-31T23:59:59Z'));

        $this->assertSame('Beta', $rows[0]->bookTitle);
        $this->assertSame(2, $rows[0]->timesBorrowed);
        $this->assertSame('Alfa', $rows[1]->bookTitle);
    }

    public function test_report_keeps_the_frozen_title_after_the_catalog_is_renamed(): void
    {
        $loans = [];
        $book = $this->book('b-1', 'Título viejo');
        $this->lend($loans, 'L1', $book, 'Novela', '2026-10-02T10:00:00Z');

        // The catalog is renamed after the loan; the loan line keeps the old title (RN-4, CA-10.4)
        $renamed = $this->book('b-1', 'Título nuevo');

        $rows = (new MostBorrowedReportService(new InMemoryReportReader($loans)))
            ->execute(new DateTimeImmutable('2026-10-01T00:00:00Z'), new DateTimeImmutable('2026-10-31T23:59:59Z'));

        $this->assertSame('Título viejo', $rows[0]->bookTitle);
        $this->assertNotSame($renamed->title(), $rows[0]->bookTitle);
    }

    public function test_range_with_no_loans_is_an_empty_list_not_an_error(): void
    {
        $rows = (new MostBorrowedReportService(new InMemoryReportReader([])))
            ->execute(new DateTimeImmutable('2026-01-01T00:00:00Z'), new DateTimeImmutable('2026-01-31T00:00:00Z'));

        $this->assertSame([], $rows);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $this->expectException(InvalidDateRange::class);
        (new MostBorrowedReportService(new InMemoryReportReader([])))
            ->execute(new DateTimeImmutable('2026-10-31T00:00:00Z'), new DateTimeImmutable('2026-10-01T00:00:00Z'));
    }
}
