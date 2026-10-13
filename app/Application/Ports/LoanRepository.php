<?php

namespace App\Application\Ports;

use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use DateTimeImmutable;

// Port: loans and their lines. Implemented by Adapters/Persistence.
// save() writes the loan and the touched books in ONE transaction (architecture §4, ADR-002).
interface LoanRepository
{
    /** @param Book[] $books books whose copies changed in this operation */
    public function save(Loan $loan, array $books): void;

    // RN-2: lines not yet returned, across all the member's loans
    public function countOpenItemsForMember(string $memberId): int;

    // RN-3: lines not yet returned and past their due date
    public function countOverdueItemsForMember(string $memberId, DateTimeImmutable $now): int;
}
