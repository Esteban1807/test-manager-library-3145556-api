<?php

namespace App\Domain\Loan;

use DateTimeImmutable;
use InvalidArgumentException;

// Inclusive range: end must not be before start (spec.md CA-10.2, RN-6: UTC instants)
final readonly class DateRange
{
    private function __construct(
        public DateTimeImmutable $from,
        public DateTimeImmutable $to,
    ) {}

    public static function of(DateTimeImmutable $from, DateTimeImmutable $to): self
    {
        if ($to < $from) {
            throw new InvalidArgumentException('La fecha final no puede ser anterior a la inicial.');
        }

        return new self($from, $to);
    }
}
