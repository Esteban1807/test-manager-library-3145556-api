<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\LoanRepository;
use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use DateTimeImmutable;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var Loan[] */
    public array $loans = [];

    public function save(Loan $loan, array $books): void
    {
        $this->loans[] = $loan;
    }

    public function countOpenItemsForMember(string $memberId): int
    {
        $count = 0;

        foreach ($this->loans as $loan) {
            if ($loan->memberId() !== $memberId) {
                continue;
            }

            foreach ($loan->items() as $item) {
                if (! $item->isReturned()) {
                    $count++;
                }
            }
        }

        return $count;
    }

    public function countOverdueItemsForMember(string $memberId, DateTimeImmutable $now): int
    {
        $count = 0;

        foreach ($this->loans as $loan) {
            if ($loan->memberId() !== $memberId) {
                continue;
            }

            foreach ($loan->items() as $item) {
                if ($item->isOverdueAt($now)) {
                    $count++;
                }
            }
        }

        return $count;
    }
}
