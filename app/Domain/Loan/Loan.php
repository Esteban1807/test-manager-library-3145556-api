<?php

namespace App\Domain\Loan;

use App\Domain\Book\Book;
use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

// Loan aggregate (spec.md HU-06, HU-07, RN-2, RN-3; architecture.md §4).
// Immutable except for returned_at on its items (CA-06.7, RN-5).
final class Loan
{
    public const MAX_ITEMS_PER_LOAN = 3;
    public const MAX_OPEN_ITEMS_PER_MEMBER = 3;
    public const LOAN_DAYS = 14;

    /** @var LoanItem[] */
    private array $items = [];

    private function __construct(
        private readonly string $id,
        private readonly string $memberId,
        private readonly string $loanedByUserId,
        private readonly DateTimeImmutable $loanedAt,
    ) {}

    public static function open(string $id, string $memberId, string $loanedByUserId, DateTimeImmutable $at): self
    {
        return new self($id, $memberId, $loanedByUserId, $at);
    }

    // CA-06.3: each line takes one copy in the same operation (Book::withdraw)
    // CA-06.6: due date is loanedAt + 14 days; title, ISBN and category are frozen
    public function addItem(string $itemId, Book $book, string $categoryName): void
    {
        if (count($this->items) >= self::MAX_ITEMS_PER_LOAN) {
            throw new InvalidArgumentException('Un préstamo tiene de 1 a 3 libros.');
        }

        foreach ($this->items as $existing) {
            if ($existing->bookId() === $book->id()) {
                throw new DuplicateBookInLoan('No se puede repetir el mismo libro en un préstamo.');
            }
        }

        $book->withdraw();

        $this->items[] = new LoanItem(
            $itemId,
            $book->id(),
            $book->title(),
            $book->isbn(),
            $categoryName,
            $this->loanedAt->modify('+'.self::LOAN_DAYS.' days'),
        );
    }

    // CA-07.1, CA-07.2: one line at a time; returning twice is an error, never adds twice
    public function returnItem(string $itemId, Book $book, DateTimeImmutable $at): void
    {
        $item = $this->findItem($itemId);

        if ($item->isReturned()) {
            throw new ItemAlreadyReturned('Esta línea ya fue devuelta.');
        }

        if ($item->bookId() !== $book->id()) {
            throw new DomainException('El libro no corresponde a la línea de préstamo.');
        }

        $book->restock();
        $item->markReturned($at);
    }

    // CA-08.3: calculated, never stored (Article VII)
    public function status(DateTimeImmutable $now): LoanStatus
    {
        if ($this->allReturned()) {
            return LoanStatus::Closed;
        }

        foreach ($this->items as $item) {
            if ($item->isOverdueAt($now)) {
                return LoanStatus::Overdue;
            }
        }

        return LoanStatus::Open;
    }

    /** @return LoanItem[] */
    public function items(): array
    {
        return $this->items;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function memberId(): string
    {
        return $this->memberId;
    }

    public function loanedAt(): DateTimeImmutable
    {
        return $this->loanedAt;
    }

    // RN-2 and RN-3: checked against the member's current state before lending.
    // The use case passes the member's counts; the domain decides.
    public static function assertMemberCanBorrow(int $openItems, int $overdueItems, int $newItems): void
    {
        if ($overdueItems > 0) {
            throw new MemberHasOverdue('El socio tiene libros vencidos y no puede pedir más.');
        }

        if ($openItems + $newItems > self::MAX_OPEN_ITEMS_PER_MEMBER) {
            throw new MemberLimitExceeded('El socio no puede tener más de 3 libros sin devolver.');
        }
    }

    private function allReturned(): bool
    {
        foreach ($this->items as $item) {
            if (! $item->isReturned()) {
                return false;
            }
        }

        return $this->items !== [];
    }

    private function findItem(string $itemId): LoanItem
    {
        foreach ($this->items as $item) {
            if ($item->id() === $itemId) {
                return $item;
            }
        }

        throw new DomainException('La línea de préstamo no existe.');
    }
}
