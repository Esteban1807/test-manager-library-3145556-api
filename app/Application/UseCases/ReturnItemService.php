<?php

namespace App\Application\UseCases;

use App\Application\Errors\BookNotFound;
use App\Application\Errors\LoanNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Domain\Loan\Loan;

// HU-07: return one line (CA-07.1). Returning twice is rejected by the domain (CA-07.2).
final class ReturnItemService
{
    public function __construct(
        private readonly LoanRepository $loans,
        private readonly BookRepository $books,
        private readonly Clock $clock,
    ) {}

    public function execute(string $loanId, string $itemId): Loan
    {
        $loan = $this->loans->findById($loanId);

        if ($loan === null) {
            throw new LoanNotFound();
        }

        $bookId = $this->bookIdOf($loan, $itemId);

        $book = $this->books->findById($bookId);

        if ($book === null) {
            throw new BookNotFound();
        }

        $loan->returnItem($itemId, $book, $this->clock->now());

        $this->loans->save($loan, [$book]);

        return $loan;
    }

    private function bookIdOf(Loan $loan, string $itemId): string
    {
        foreach ($loan->items() as $item) {
            if ($item->id() === $itemId) {
                return $item->bookId();
            }
        }

        throw new LoanNotFound();
    }
}
