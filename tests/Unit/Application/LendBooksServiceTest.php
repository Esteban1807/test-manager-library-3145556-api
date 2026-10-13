<?php

namespace Tests\Unit\Application;

use App\Application\Errors\BookNotFound;
use App\Application\Errors\MemberNotFound;
use App\Application\UseCases\LendBooksService;
use App\Domain\Book\Book;
use App\Domain\Book\BookNotAvailable;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use App\Domain\Loan\DuplicateBookInLoan;
use App\Domain\Loan\Loan;
use App\Domain\Loan\MemberHasOverdue;
use App\Domain\Loan\MemberLimitExceeded;
use App\Domain\Member\DocumentNumber;
use App\Domain\Member\Member;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Application\Fakes\FixedClock;
use Tests\Unit\Application\Fakes\FixedCategoryReader;
use Tests\Unit\Application\Fakes\InMemoryBookRepository;
use Tests\Unit\Application\Fakes\InMemoryLoanRepository;
use Tests\Unit\Application\Fakes\InMemoryMemberRepository;
use Tests\Unit\Application\Fakes\SequentialIdGenerator;

class LendBooksServiceTest extends TestCase
{
    private const NOVELA = '11111111-1111-4111-8111-111111111111';
    private const MEMBER = 'm-1';
    private const LIBRARIAN = 'u-1';

    private InMemoryMemberRepository $members;
    private InMemoryBookRepository $books;
    private InMemoryLoanRepository $loans;
    private FixedClock $clock;
    private SequentialIdGenerator $ids;

    protected function setUp(): void
    {
        $this->members = new InMemoryMemberRepository();
        $this->books = new InMemoryBookRepository();
        $this->loans = new InMemoryLoanRepository();
        $this->ids = new SequentialIdGenerator(); // shared, so every loan gets a unique id
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z'));

        $this->members->save(Member::create(self::MEMBER, 'Luis Pérez', DocumentNumber::fromString('123')));

        foreach (['b-1', 'b-2', 'b-3', 'b-4'] as $i => $id) {
            $this->addBook($id, 'Libro '.$id, 2, '978030747472'.$i);
        }
    }

    private function addBook(string $id, string $title, int $copies, string $isbn): void
    {
        $this->books->save(Book::create($id, $title, 'Autor', Isbn::fromString($isbn), self::NOVELA, Quantity::of($copies)));
    }

    private function service(): LendBooksService
    {
        return new LendBooksService(
            $this->members,
            $this->books,
            $this->loans,
            new FixedCategoryReader(),
            $this->ids,
            $this->clock,
        );
    }

    // HU-06

    public function test_lends_books_and_freezes_title_and_category(): void
    {
        $loan = $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-1', 'b-2']);

        $this->assertCount(2, $loan->items());
        $this->assertSame('Novela', $loan->items()[0]->categoryName());
        $this->assertSame('Libro b-1', $loan->items()[0]->bookTitle());
        $this->assertSame(1, $this->books->findById('b-1')->available());
        $this->assertCount(1, $this->loans->loans);
    }

    public function test_more_than_three_books_in_one_loan_is_rejected(): void
    {
        $this->addBook('b-5', 'Libro b-5', 2, '9780000000005');

        $this->expectException(InvalidArgumentException::class);
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-1', 'b-2', 'b-3', 'b-4']);
    }

    public function test_same_book_twice_in_one_loan_is_rejected(): void
    {
        $this->expectException(DuplicateBookInLoan::class);
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-1', 'b-1']);
    }

    public function test_without_copies_nothing_is_registered(): void
    {
        $this->addBook('b-0', 'Agotado', 1, '9780000000000');
        $this->books->findById('b-0')->withdraw(); // no copies left

        try {
            $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-1', 'b-0']);
            $this->fail('Se esperaba BookNotAvailable');
        } catch (BookNotAvailable) {
            $this->assertCount(0, $this->loans->loans);
        }
    }

    public function test_member_with_three_open_items_cannot_borrow_more(): void
    {
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-1', 'b-2']);
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-3']);

        $this->expectException(MemberLimitExceeded::class);
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-4']);
    }

    public function test_member_with_an_overdue_item_cannot_borrow(): void
    {
        $this->clock = new FixedClock(new DateTimeImmutable('2026-09-01T10:00:00Z'));
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-1']);
        $this->clock = new FixedClock(new DateTimeImmutable('2026-10-13T12:00:00Z'));

        $this->expectException(MemberHasOverdue::class);
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['b-2']);
    }

    public function test_unknown_member_is_not_found(): void
    {
        $this->expectException(MemberNotFound::class);
        $this->service()->execute('nadie', self::LIBRARIAN, ['b-1']);
    }

    public function test_unknown_book_is_not_found(): void
    {
        $this->expectException(BookNotFound::class);
        $this->service()->execute(self::MEMBER, self::LIBRARIAN, ['no-existe']);
    }
}
