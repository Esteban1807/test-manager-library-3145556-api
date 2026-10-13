<?php

namespace App\Domain\Book;

use InvalidArgumentException;

// ISBN-13: exactly 13 digits, digits only (spec.md CA-03.2, data-model §3)
final readonly class Isbn
{
    private function __construct(public string $value) {}

    public static function fromString(string $raw): self
    {
        $value = trim($raw);

        if (preg_match('/^\d{13}$/', $value) !== 1) {
            throw new InvalidArgumentException('El ISBN debe tener exactamente 13 dígitos.');
        }

        return new self($value);
    }
}
