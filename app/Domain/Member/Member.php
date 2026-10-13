<?php

namespace App\Domain\Member;

use InvalidArgumentException;

// Borrower (spec.md HU-05). Members do not log in and are never deleted (CA-05.3).
final class Member
{
    private const NAME_MAX = 200;

    private function __construct(
        private readonly string $id,
        private readonly string $fullName,
        private readonly DocumentNumber $documentNumber,
    ) {}

    public static function create(string $id, string $fullName, DocumentNumber $documentNumber): self
    {
        $name = trim($fullName);

        if ($name === '' || mb_strlen($name) > self::NAME_MAX) {
            throw new InvalidArgumentException('El nombre es obligatorio y no puede superar 200 caracteres.');
        }

        return new self($id, $name, $documentNumber);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function fullName(): string
    {
        return $this->fullName;
    }

    public function documentNumber(): string
    {
        return $this->documentNumber->value;
    }
}
