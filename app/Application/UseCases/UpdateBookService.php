<?php

namespace App\Application\UseCases;

use App\Application\Errors\BookNotFound;
use App\Application\Ports\BookRepository;
use App\Domain\Book\Book;
use App\Domain\Book\Quantity;

// HU-03: edit title, author, category and total (CA-03.3)
final class UpdateBookService
{
    public function __construct(private readonly BookRepository $books) {}

    public function execute(string $id, string $title, string $author, string $categoryId, int $totalCopies): Book
    {
        $book = $this->findActive($id);

        $book->update($title, $author, $categoryId);
        $book->changeTotal(Quantity::of($totalCopies));

        $this->books->save($book);

        return $book;
    }

    private function findActive(string $id): Book
    {
        $book = $this->books->findById($id);

        if ($book === null || $book->isDeleted()) {
            throw new BookNotFound();
        }

        return $book;
    }
}
