<?php

namespace App\Application\Ports;

use App\Domain\Book\Book;

// Port: persistence of the Book aggregate. Implemented by Adapters/Persistence.
interface BookRepository
{
    // Returns deleted books too; the caller decides (CA-04.3 hides them as 404)
    public function findById(string $id): ?Book;

    // Active books only (CA-03.2: ISBN is unique among active books)
    public function findByIsbnActive(string $isbn): ?Book;

    public function save(Book $book): void;

    // CA-04.1: active books, title or author partial match, category filter, ordered by title
    public function search(?string $text, ?string $categoryId, int $page, int $size): SearchResult;
}
