<?php

namespace App\Application\UseCases;

use App\Application\Errors\BookHasOpenLoans;
use App\Application\Errors\BookNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\Clock;
use App\Application\Ports\OpenLoansReader;

// HU-03: logical deletion (CA-03.5). Refused while the book has open loans.
final class DeleteBookService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly OpenLoansReader $openLoans,
        private readonly Clock $clock,
    ) {}

    public function execute(string $id): void
    {
        $book = $this->books->findById($id);

        if ($book === null || $book->isDeleted()) {
            throw new BookNotFound();
        }

        if ($this->openLoans->bookHasOpenLoans($id)) {
            throw new BookHasOpenLoans();
        }

        $book->softDelete($this->clock->now());

        $this->books->save($book);
    }
}
