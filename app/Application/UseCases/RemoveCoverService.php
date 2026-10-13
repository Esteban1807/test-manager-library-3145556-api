<?php

namespace App\Application\UseCases;

use App\Application\Errors\BookNotFound;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\Book\Book;

// Removes the cover: the key goes first, the binary after (data-model §7 order)
final class RemoveCoverService
{
    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $storage,
    ) {}

    public function execute(string $id): Book
    {
        $book = $this->books->findById($id);

        if ($book === null || $book->isDeleted()) {
            throw new BookNotFound();
        }

        $key = $book->coverKey();

        if ($key === null) {
            return $book;
        }

        $book->detachCover();
        $this->books->save($book);
        $this->storage->delete($key);

        return $book;
    }
}
