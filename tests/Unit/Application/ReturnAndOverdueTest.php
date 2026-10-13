<?php

namespace Tests\Unit\Application;

use App\Application\Errors\LoanNotFound;
use App\Application\UseCases\OverdueService;
use App\Application\UseCases\ReturnItemService;
use App\Domain\Book\Book;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use App\Domain\Loan\ItemAlreadyReturned;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanStatus;
use App\Domain\Member\DocumentNumber;
use App\Domain\Member\Member;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Application\Fakes\FixedClock;
use Tests\Unit\Application\Fakes\InMemoryBookRepository;
use Tests\Unit\Application\Fakes\InMemoryLoanRepository;
use Tests\Unit\Application\Fakes\InMemoryMemberRepository;

class ReturnAndOverdueTest extends TestCase
{
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private InMemoryMemberRepository $members;

    protected function setUp(): void
    {
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->members = new InMemoryMemberRepository();

        $this->members->save(Member::create('m-1', 'Luis Pérez', DocumentNumber::fromString('123')));
    }

    // Seeds a loan of one book on the given day, with item id `i-<loanId>`
    private function lend(string $loanId, string $bookId, string $loanedAt): void
    {
        $book = Book::create(
            $bookId,
            'Libro '.$bookId,
            'Autor',
            Isbn::fromString('9780307474728'),
            '11111111-1111-4111-8111-111111111111',
            Quantity::of(2),
        );

        $loan = Loan::open($loanId, 'm-1', 'u-1', new DateTimeImmutable($loanedAt));
        $loan->addItem('i-'.$loanId, $book, 'Novela');

        $this->books->save($book);
        $this->loans->save($loan, [$book]);
    }

    // HU-07

    public function test_returning_the_only_line_restocks_book_and_closes_loan(): void
    {
        $this->lend('L1', 'b-1', '2026-09-01T10:00:00Z');
        $clock = new FixedClock(new DateTimeImmutable('2026-09-05T10:00:00Z'));

        $loan = (new ReturnItemService($this->loans, $this->books, $clock))->execute('L1', 'i-L1');

        $this->assertSame(2, $this->books->findById('b-1')->available());
        $this->assertSame(LoanStatus::Closed, $loan->status($clock->now()));
    }

    public function test_returning_twice_is_rejected(): void
    {
        $this->lend('L1', 'b-1', '2026-09-01T10:00:00Z');
        $service = new ReturnItemService($this->loans, $this->books, new FixedClock(new DateTimeImmutable('2026-09-05T10:00:00Z')));
        $service->execute('L1', 'i-L1');

        $this->expectException(ItemAlreadyReturned::class);
        $service->execute('L1', 'i-L1');
    }

    public function test_unknown_loan_is_not_found(): void
    {
        $this->expectException(LoanNotFound::class);
        (new ReturnItemService($this->loans, $this->books, new FixedClock(new DateTimeImmutable('2026-09-05T10:00:00Z'))))
            ->execute('no-existe', 'i-x');
    }

    // HU-09

    public function test_overdue_list_is_sorted_by_most_days_late_and_includes_member_name(): void
    {
        $this->lend('L1', 'b-1', '2026-09-01T10:00:00Z'); // due 09-15
        $this->lend('L2', 'b-2', '2026-09-10T10:00:00Z'); // due 09-24
        $clock = new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z'));

        $lines = (new OverdueService($this->loans, $this->members, $clock))->execute();

        $this->assertCount(2, $lines);
        $this->assertSame('L1', $lines[0]->loanId);
        $this->assertSame(28, $lines[0]->daysLate);
        $this->assertSame('L2', $lines[1]->loanId);
        $this->assertSame(19, $lines[1]->daysLate);
        $this->assertSame('Luis Pérez', $lines[0]->memberName);
    }

    public function test_returned_lines_are_not_overdue(): void
    {
        $this->lend('L1', 'b-1', '2026-09-01T10:00:00Z');
        (new ReturnItemService($this->loans, $this->books, new FixedClock(new DateTimeImmutable('2026-09-05T10:00:00Z'))))
            ->execute('L1', 'i-L1');

        $lines = (new OverdueService($this->loans, $this->members, new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z'))))
            ->execute();

        $this->assertCount(0, $lines);
    }
}
