<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\LoanRepository;
use App\Application\Ports\SearchResult;
use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanStatus;
use DateTimeImmutable;

final class InMemoryLoanRepository implements LoanRepository
{
    /** @var array<string, Loan> */
    public array $loans = [];

    public function findById(string $id): ?Loan
    {
        return $this->loans[$id] ?? null;
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        int $page,
        int $size,
        DateTimeImmutable $now,
    ): SearchResult {
        $matches = array_values(array_filter($this->loans, function (Loan $loan) use ($from, $to, $status, $memberId, $now): bool {
            if ($from !== null && $loan->loanedAt() < $from) {
                return false;
            }

            if ($to !== null && $loan->loanedAt() > $to) {
                return false;
            }

            if ($memberId !== null && $loan->memberId() !== $memberId) {
                return false;
            }

            return $status === null || $loan->status($now) === $status;
        }));

        usort($matches, fn (Loan $a, Loan $b) => $b->loanedAt() <=> $a->loanedAt());

        return new SearchResult(array_slice($matches, ($page - 1) * $size, $size), $page, $size, count($matches));
    }

    public function findWithOpenItems(): array
    {
        return array_values(array_filter(
            $this->loans,
            fn (Loan $loan) => array_filter($loan->items(), fn ($item) => ! $item->isReturned()) !== [],
        ));
    }

    public function save(Loan $loan, array $books): void
    {
        $this->loans[$loan->id()] = $loan;
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
