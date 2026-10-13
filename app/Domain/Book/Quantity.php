<?php

namespace App\Domain\Book;

use InvalidArgumentException;

// Number of copies: at least 1 (spec.md CA-03.3, tasks T-03)
final readonly class Quantity
{
    private function __construct(public int $value) {}

    public static function of(int $value): self
    {
        if ($value < 1) {
            throw new InvalidArgumentException('La cantidad debe ser de al menos 1.');
        }

        return new self($value);
    }
}
