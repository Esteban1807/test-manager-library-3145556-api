<?php

namespace App\Application\UseCases;

use App\Application\Errors\IsbnTaken;
use App\Application\Ports\BookRepository;
use App\Domain\Book\Book;
use App\Domain\Book\Isbn;
use App\Domain\Book\Quantity;

// HU-03: create a book (CA-03.1, CA-03.2)
final class CreateBookService
{
    public function __construct(private readonly BookRepository $books) {}

    public function execute(
        string $id,
        string $title,
        string $author,
        string $isbn,
        string $categoryId,
        int $totalCopies,
    ): Book {
        $isbnValue = Isbn::fromString($isbn);

        if ($this->books->findByIsbnActive($isbnValue->value) !== null) {
            throw new IsbnTaken();
        }

        $book = Book::create($id, $title, $author, $isbnValue, $categoryId, Quantity::of($totalCopies));

        $this->books->save($book);

        return $book;
    }
}
