<?php

namespace App\Domain\Member;

use InvalidArgumentException;

// Member's document: required, max 30 characters (data-model §3, spec.md CA-05.1)
final readonly class DocumentNumber
{
    private function __construct(public string $value) {}

    public static function fromString(string $raw): self
    {
        $value = trim($raw);

        if ($value === '' || mb_strlen($value) > 30) {
            throw new InvalidArgumentException('El documento es obligatorio y no puede superar 30 caracteres.');
        }

        return new self($value);
    }
}
