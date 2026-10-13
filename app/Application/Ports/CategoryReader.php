<?php

namespace App\Application\Ports;

// Port: category names are copied into the loan line at lending time (RN-4)
interface CategoryReader
{
    public function nameOf(string $categoryId): ?string;
}
