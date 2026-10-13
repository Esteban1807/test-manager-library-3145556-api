<?php

namespace App\Domain\Loan;

use DateTimeImmutable;

// One book inside a loan. Title, ISBN and category are frozen at loan time (RN-4, ADR-004).
final class LoanItem
{
    public function __construct(
        private readonly string $id,
        private readonly string $bookId,
        private readonly string $bookTitle,
        private readonly string $isbn,
        private readonly string $categoryName,
        private readonly DateTimeImmutable $dueAt,
        private ?DateTimeImmutable $returnedAt = null,
    ) {}

    public function id(): string
    {
        return $this->id;
    }

    public function bookId(): string
    {
        return $this->bookId;
    }

    public function bookTitle(): string
    {
        return $this->bookTitle;
    }

    public function isbn(): string
    {
        return $this->isbn;
    }

    public function categoryName(): string
    {
        return $this->categoryName;
    }

    public function dueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function returnedAt(): ?DateTimeImmutable
    {
        return $this->returnedAt;
    }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdueAt(DateTimeImmutable $now): bool
    {
        return ! $this->isReturned() && $this->dueAt < $now;
    }

    // Whole days past due, 0 if not late (spec.md CA-09.1)
    public function daysLateAt(DateTimeImmutable $now): int
    {
        if (! $this->isOverdueAt($now)) {
            return 0;
        }

        return intdiv($now->getTimestamp() - $this->dueAt->getTimestamp(), 86400);
    }

    // Only the return date changes after creation (spec.md CA-06.7, RN-5)
    public function markReturned(DateTimeImmutable $at): void
    {
        $this->returnedAt = $at;
    }
}
