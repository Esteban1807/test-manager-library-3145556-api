<?php

namespace App\Application\Ports;

// Port: answers whether a book is lent out right now (CA-03.5 blocks deletion)
interface OpenLoansReader
{
    public function bookHasOpenLoans(string $bookId): bool;
}
