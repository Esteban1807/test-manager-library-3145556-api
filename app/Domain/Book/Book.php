<?php

namespace App\Domain\Book;

use DateTimeImmutable;
use DomainException;
use InvalidArgumentException;

// Aggregate root for a catalog book (spec.md HU-03, RN-1, RN-5; data-model §3).
// Invariants: 0 <= available <= total, total >= 1. `version` is the optimistic
// concurrency token (ADR-002): it moves on every state change.
final class Book
{
    private const TITLE_MAX = 250;

    private function __construct(
        private readonly string $id,
        private string $title,
        private string $author,
        private readonly Isbn $isbn,
        private string $categoryId,
        private Quantity $totalCopies,
        private int $availableCopies,
        private int $version,
        private ?string $coverKey,
        private ?DateTimeImmutable $deletedAt,
    ) {}

    // CA-03.1: available starts equal to total
    public static function create(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        Quantity $totalCopies,
    ): self {
        return new self(
            $id,
            self::normalizeTitle($title),
            trim($author),
            $isbn,
            $categoryId,
            $totalCopies,
            $totalCopies->value,
            0,
            null,
            null,
        );
    }

    // Lend one copy (CA-06.3)
    public function withdraw(): void
    {
        $this->assertActive();

        if ($this->availableCopies < 1) {
            throw new BookNotAvailable('No quedan ejemplares disponibles.');
        }

        $this->availableCopies--;
        $this->version++;
    }

    // Return one copy (CA-07.1). Never above total (RN-1, CA-07.3)
    public function restock(): void
    {
        $this->assertActive();

        if ($this->availableCopies >= $this->totalCopies->value) {
            throw new DomainException('Los disponibles no pueden superar el total.');
        }

        $this->availableCopies++;
        $this->version++;
    }

    // CA-03.3: changing the total moves available by the same difference;
    // available can never drop below 0
    public function changeTotal(Quantity $newTotal): void
    {
        $this->assertActive();

        $difference = $newTotal->value - $this->totalCopies->value;
        $newAvailable = $this->availableCopies + $difference;

        if ($newAvailable < 0) {
            throw new InvalidArgumentException('El total no puede dejar disponibles por debajo de 0.');
        }

        $this->totalCopies = $newTotal;
        $this->availableCopies = $newAvailable;
        $this->version++;
    }

    // CA-03.1 edit: replaces title, author and category (HU-03, PUT /books/{id})
    public function update(string $title, string $author, string $categoryId): void
    {
        $this->assertActive();

        $this->title = self::normalizeTitle($title);
        $this->author = trim($author);
        $this->categoryId = $categoryId;
        $this->version++;
    }

    // CA-03.4: the database keeps an opaque key, never a path or bytes
    public function attachCover(string $coverKey): void
    {
        $this->assertActive();

        $this->coverKey = $coverKey;
        $this->version++;
    }

    // Order matters: clear the key first, then the caller deletes the binary (data-model §7)
    public function detachCover(): void
    {
        $this->assertActive();

        $this->coverKey = null;
        $this->version++;
    }

    public function coverKey(): ?string
    {
        return $this->coverKey;
    }

    // CA-03.5: logical deletion. Open-loan check belongs to the use case (409 BOOK_HAS_OPEN_LOANS)
    public function softDelete(DateTimeImmutable $at): void
    {
        if ($this->deletedAt !== null) {
            return;
        }

        $this->deletedAt = $at;
        $this->version++;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function title(): string
    {
        return $this->title;
    }

    public function isbn(): string
    {
        return $this->isbn->value;
    }

    public function categoryId(): string
    {
        return $this->categoryId;
    }

    public function available(): int
    {
        return $this->availableCopies;
    }

    public function total(): int
    {
        return $this->totalCopies->value;
    }

    public function version(): int
    {
        return $this->version;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function assertActive(): void
    {
        if ($this->deletedAt !== null) {
            throw new DomainException('El libro está dado de baja.');
        }
    }

    private static function normalizeTitle(string $title): string
    {
        $normalized = trim($title);

        if ($normalized === '' || mb_strlen($normalized) > self::TITLE_MAX) {
            throw new InvalidArgumentException('El título es obligatorio y no puede superar 250 caracteres.');
        }

        return $normalized;
    }
}
