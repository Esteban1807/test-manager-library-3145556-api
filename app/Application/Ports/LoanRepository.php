<?php

namespace App\Application\Ports;

use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanStatus;
use DateTimeImmutable;

// Port: loans and their lines. Implemented by Adapters/Persistence.
// save() writes the loan and the touched books in ONE transaction (architecture §4, ADR-002).
interface LoanRepository
{
    public function findById(string $id): ?Loan;

    // CA-08.2: filters by date range, calculated status and member; newest first, paginated
    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        int $page,
        int $size,
        DateTimeImmutable $now,
    ): SearchResult;

    // Loans that still have at least one line not returned (feeds the overdue list, HU-09)
    /** @return Loan[] */
    public function findWithOpenItems(): array;

    /** @param Book[] $books books whose copies changed in this operation */
    public function save(Loan $loan, array $books): void;

    // RN-2: lines not yet returned, across all the member's loans
    public function countOpenItemsForMember(string $memberId): int;

    // RN-3: lines not yet returned and past their due date
    public function countOverdueItemsForMember(string $memberId, DateTimeImmutable $now): int;
}
