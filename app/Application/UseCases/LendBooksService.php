<?php

namespace App\Application\UseCases;

use App\Application\Errors\BookNotFound;
use App\Application\Errors\MemberNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CategoryReader;
use App\Application\Ports\Clock;
use App\Application\Ports\IdGenerator;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\MemberRepository;
use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use InvalidArgumentException;

// HU-06: register a loan of 1 to 3 books for one member (CA-06.1 to CA-06.6).
// Nothing is saved unless every line succeeds (CA-06.3).
final class LendBooksService
{
    public function __construct(
        private readonly MemberRepository $members,
        private readonly BookRepository $books,
        private readonly LoanRepository $loans,
        private readonly CategoryReader $categories,
        private readonly IdGenerator $ids,
        private readonly Clock $clock,
    ) {}

    /** @param string[] $bookIds */
    public function execute(string $memberId, string $loanedByUserId, array $bookIds): Loan
    {
        // CA-06.1: one loan holds 1 to 3 lines
        if (count($bookIds) < 1 || count($bookIds) > Loan::MAX_ITEMS_PER_LOAN) {
            throw new InvalidArgumentException('Un préstamo tiene de 1 a 3 libros.');
        }

        if ($this->members->findById($memberId) === null) {
            throw new MemberNotFound();
        }

        $now = $this->clock->now();

        Loan::assertMemberCanBorrow(
            openItems: $this->loans->countOpenItemsForMember($memberId),
            overdueItems: $this->loans->countOverdueItemsForMember($memberId, $now),
            newItems: count($bookIds),
        );

        $loan = Loan::open($this->ids->next(), $memberId, $loanedByUserId, $now);
        $touched = [];

        foreach ($bookIds as $bookId) {
            $book = $this->books->findById($bookId);

            if ($book === null || $book->isDeleted()) {
                throw new BookNotFound();
            }

            $categoryName = $this->categories->nameOf($book->categoryId()) ?? '';

            $loan->addItem($this->ids->next(), $book, $categoryName);
            $touched[] = $book;
        }

        $this->loans->save($loan, $touched);

        return $loan;
    }
}
