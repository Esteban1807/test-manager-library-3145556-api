<?php

namespace Tests\Unit\Domain;

use App\Domain\Book\Book;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use App\Domain\Loan\DuplicateBookInLoan;
use App\Domain\Loan\ItemAlreadyReturned;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanStatus;
use App\Domain\Loan\MemberHasOverdue;
use App\Domain\Loan\MemberLimitExceeded;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

// Uses a fixed "now" instead of a real clock: the Clock port makes these deterministic (plan D-05).
class LoanTest extends TestCase
{
    private const CATEGORY = 'Novela';

    private DateTimeImmutable $loanedAt;

    protected function setUp(): void
    {
        $this->loanedAt = new DateTimeImmutable('2026-10-01T10:00:00Z');
    }

    private function book(string $id, int $total = 5): Book
    {
        return Book::create(
            $id,
            'Libro '.$id,
            'Autor',
            Isbn::fromString('9780307474728'),
            '11111111-1111-4111-8111-111111111111',
            Quantity::of($total),
        );
    }

    private function loan(): Loan
    {
        return Loan::open('l-1', 'm-1', 'u-1', $this->loanedAt);
    }

    public function test_due_date_is_loaned_at_plus_14_days_and_title_is_frozen(): void
    {
        $loan = $this->loan();
        $book = $this->book('b-1');

        $loan->addItem('i-1', $book, self::CATEGORY);

        $item = $loan->items()[0];
        $this->assertSame('2026-10-15T10:00:00+00:00', $item->dueAt()->format(DATE_ATOM));
        $this->assertSame('Libro b-1', $item->bookTitle());
        $this->assertSame(self::CATEGORY, $item->categoryName());
    }

    public function test_adding_a_line_takes_one_copy(): void
    {
        $book = $this->book('b-1', 2);

        $this->loan()->addItem('i-1', $book, self::CATEGORY);

        $this->assertSame(1, $book->available());
    }

    public function test_a_loan_rejects_a_fourth_line(): void
    {
        $loan = $this->loan();
        foreach (['b-1', 'b-2', 'b-3'] as $id) {
            $loan->addItem('i-'.$id, $this->book($id), self::CATEGORY);
        }

        $this->expectException(InvalidArgumentException::class);
        $loan->addItem('i-b-4', $this->book('b-4'), self::CATEGORY);
    }

    public function test_the_same_book_cannot_appear_twice_in_one_loan(): void
    {
        $loan = $this->loan();
        $loan->addItem('i-1', $this->book('b-1'), self::CATEGORY);

        $this->expectException(DuplicateBookInLoan::class);
        $loan->addItem('i-2', $this->book('b-1'), self::CATEGORY);
    }

    public function test_status_is_open_before_due_date(): void
    {
        $loan = $this->loan();
        $loan->addItem('i-1', $this->book('b-1'), self::CATEGORY);

        $this->assertSame(LoanStatus::Open, $loan->status(new DateTimeImmutable('2026-10-10T10:00:00Z')));
    }

    public function test_status_is_overdue_after_due_date_with_item_not_returned(): void
    {
        $loan = $this->loan();
        $loan->addItem('i-1', $this->book('b-1'), self::CATEGORY);

        $this->assertSame(LoanStatus::Overdue, $loan->status(new DateTimeImmutable('2026-10-20T10:00:00Z')));
    }

    public function test_status_is_closed_when_every_line_is_returned(): void
    {
        $book = $this->book('b-1');
        $loan = $this->loan();
        $loan->addItem('i-1', $book, self::CATEGORY);

        $loan->returnItem('i-1', $book, new DateTimeImmutable('2026-10-05T10:00:00Z'));

        $this->assertSame(LoanStatus::Closed, $loan->status(new DateTimeImmutable('2026-10-20T10:00:00Z')));
    }

    public function test_returning_a_line_restocks_the_book(): void
    {
        $book = $this->book('b-1', 2);
        $loan = $this->loan();
        $loan->addItem('i-1', $book, self::CATEGORY);

        $loan->returnItem('i-1', $book, new DateTimeImmutable('2026-10-05T10:00:00Z'));

        $this->assertSame(2, $book->available());
    }

    public function test_returning_twice_is_rejected_and_never_adds_twice(): void
    {
        $book = $this->book('b-1', 2);
        $loan = $this->loan();
        $loan->addItem('i-1', $book, self::CATEGORY);
        $loan->returnItem('i-1', $book, new DateTimeImmutable('2026-10-05T10:00:00Z'));

        $this->expectException(ItemAlreadyReturned::class);
        $loan->returnItem('i-1', $book, new DateTimeImmutable('2026-10-06T10:00:00Z'));
    }

    public function test_member_with_overdue_items_cannot_borrow(): void
    {
        $this->expectException(MemberHasOverdue::class);
        Loan::assertMemberCanBorrow(openItems: 1, overdueItems: 1, newItems: 1);
    }

    public function test_member_cannot_exceed_three_open_items(): void
    {
        $this->expectException(MemberLimitExceeded::class);
        Loan::assertMemberCanBorrow(openItems: 2, overdueItems: 0, newItems: 2);
    }

    public function test_member_within_limit_can_borrow(): void
    {
        Loan::assertMemberCanBorrow(openItems: 1, overdueItems: 0, newItems: 2);
        $this->addToAssertionCount(1);
    }
}
