<?php

namespace App\Application\Ports;

// Port: category names are copied into the loan line at lending time (RN-4)
interface CategoryReader
{
    public function nameOf(string $categoryId): ?string;

    // GET /categories: the five fixed categories, ordered by name (api-contract §3.3)
    /** @return array<int, array{id: string, name: string}> */
    public function all(): array;
}
