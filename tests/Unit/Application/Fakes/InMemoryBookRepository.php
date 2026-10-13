<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\BookRepository;
use App\Application\Ports\SearchResult;
use App\Domain\Book\Book;

final class InMemoryBookRepository implements BookRepository
{
    /** @var array<string, Book> */
    public array $books = [];

    public function findById(string $id): ?Book
    {
        return $this->books[$id] ?? null;
    }

    public function findByIsbnActive(string $isbn): ?Book
    {
        foreach ($this->books as $book) {
            if (! $book->isDeleted() && $book->isbn() === $isbn) {
                return $book;
            }
        }

        return null;
    }

    public function save(Book $book): void
    {
        $this->books[$book->id()] = $book;
    }

    public function search(?string $text, ?string $categoryId, int $page, int $size): SearchResult
    {
        $matches = array_values(array_filter($this->books, function (Book $book) use ($text, $categoryId): bool {
            if ($book->isDeleted()) {
                return false;
            }

            if ($categoryId !== null && $book->categoryId() !== $categoryId) {
                return false;
            }

            if ($text === null || $text === '') {
                return true;
            }

            $needle = mb_strtolower($text);

            return str_contains(mb_strtolower($book->title()), $needle);
        }));

        usort($matches, fn (Book $a, Book $b) => strcmp($a->title(), $b->title()));

        $items = array_slice($matches, ($page - 1) * $size, $size);

        return new SearchResult($items, $page, $size, count($matches));
    }
}
