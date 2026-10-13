<?php

namespace Tests\Integration;

use App\Adapters\Persistence\EloquentBookRepository;
use App\Adapters\Persistence\EloquentLoanRepository;
use App\Adapters\Persistence\EloquentMemberRepository;
use App\Adapters\Persistence\EloquentUserRepository;
use App\Domain\Book\Book;
use App\Domain\Book\BookNotAvailable;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;
use App\Domain\Loan\Loan;
use App\Domain\Member\DocumentNumber;
use App\Domain\Member\Member;
use App\Domain\User\Role;
use App\Domain\User\User;
use DateTimeImmutable;
use Tests\Concerns\ResetsLibrarySchema;
use Tests\TestCase;

// RNF-2 / ADR-002: two loans of the LAST copy, both read before either is saved.
// The first save wins; the second must be refused, not silently double-lent.
// Ids are real UUIDs: the columns are uuid in PostgreSQL (data-model §3).
class ConcurrencyTest extends TestCase
{
    use ResetsLibrarySchema;

    private const NOVELA = '11111111-1111-4111-8111-111111111111';
    private const MEMBER_1 = '00000000-0000-4000-8000-000000000001';
    private const MEMBER_2 = '00000000-0000-4000-8000-000000000002';
    private const USER_1 = '00000000-0000-4000-8000-000000000011';
    private const USER_2 = '00000000-0000-4000-8000-000000000012';
    private const BOOK = '00000000-0000-4000-8000-000000000101';
    private const LOAN_A = '00000000-0000-4000-8000-000000000201';
    private const LOAN_B = '00000000-0000-4000-8000-000000000202';
    private const ITEM_A = '00000000-0000-4000-8000-000000000301';
    private const ITEM_B = '00000000-0000-4000-8000-000000000302';

    public function test_second_loan_of_the_last_copy_is_refused(): void
    {
        $books = new EloquentBookRepository();
        $loans = new EloquentLoanRepository();
        $at = new DateTimeImmutable('2026-10-13T12:00:00Z');

        // Foreign keys need a member and a user to exist first
        (new EloquentMemberRepository())->save(Member::create(self::MEMBER_1, 'Socio Uno', DocumentNumber::fromString('111')));
        (new EloquentMemberRepository())->save(Member::create(self::MEMBER_2, 'Socio Dos', DocumentNumber::fromString('222')));
        (new EloquentUserRepository())->save(User::provision(self::USER_1, 'ana', 'hash', Role::Librarian));
        (new EloquentUserRepository())->save(User::provision(self::USER_2, 'luis', 'hash', Role::Librarian));

        $books->save(Book::create(self::BOOK, 'Último', 'Autor', Isbn::fromString('9780307474728'), self::NOVELA, Quantity::of(1)));

        // Two bibliotecarios read the same version of the book
        $readA = $books->findById(self::BOOK);
        $readB = $books->findById(self::BOOK);

        $loanA = Loan::open(self::LOAN_A, self::MEMBER_1, self::USER_1, $at);
        $loanA->addItem(self::ITEM_A, $readA, 'Novela');
        $loans->save($loanA, [$readA]);

        $loanB = Loan::open(self::LOAN_B, self::MEMBER_2, self::USER_2, $at);
        $loanB->addItem(self::ITEM_B, $readB, 'Novela');

        $this->expectException(BookNotAvailable::class);
        $loans->save($loanB, [$readB]);
    }
}
