<?php

namespace App\Application\UseCases;

use App\Application\Errors\BookNotFound;
use App\Application\Errors\InvalidCover;
use App\Application\Ports\BookRepository;
use App\Application\Ports\CoverStorage;
use App\Domain\Book\Book;

// HU-03 / CA-03.4: JPG or PNG up to 2 MB. The database keeps only the opaque key.
final class UploadCoverService
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const ALLOWED_MIME = ['image/jpeg', 'image/png'];

    public function __construct(
        private readonly BookRepository $books,
        private readonly CoverStorage $storage,
    ) {}

    public function execute(string $id, string $bytes, string $mimeType): Book
    {
        $book = $this->books->findById($id);

        if ($book === null || $book->isDeleted()) {
            throw new BookNotFound();
        }

        if (strlen($bytes) > self::MAX_BYTES) {
            throw new InvalidCover('La portada no puede superar 2 MB.');
        }

        if (! in_array($mimeType, self::ALLOWED_MIME, true)) {
            throw new InvalidCover('La portada debe ser JPG o PNG.');
        }

        $previousKey = $book->coverKey();
        $book->attachCover($this->storage->store($id, $bytes, $mimeType));

        $this->books->save($book);

        if ($previousKey !== null) {
            $this->storage->delete($previousKey);
        }

        return $book;
    }
}
