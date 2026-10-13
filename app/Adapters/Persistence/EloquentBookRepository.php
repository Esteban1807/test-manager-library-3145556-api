<?php

namespace App\Adapters\Persistence;

use App\Adapters\Persistence\Models\BookRecord;
use App\Application\Ports\BookRepository;
use App\Application\Ports\SearchResult;
use App\Domain\Book\Book;

final class EloquentBookRepository implements BookRepository
{
    public function findById(string $id): ?Book
    {
        $record = BookRecord::query()->find($id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByIsbnActive(string $isbn): ?Book
    {
        $record = BookRecord::query()->whereNull('deleted_at')->where('isbn', $isbn)->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function save(Book $book): void
    {
        BookRecord::query()->updateOrCreate(['id' => $book->id()], [
            'title' => $book->title(),
            'author' => $book->author(),
            'isbn' => $book->isbn(),
            'category_id' => $book->categoryId(),
            'total_copies' => $book->total(),
            'available_copies' => $book->available(),
            'version' => $book->version(),
            'cover_key' => $book->coverKey(),
            'deleted_at' => $book->deletedAt(),
        ]);
    }

    public function search(?string $text, ?string $categoryId, int $page, int $size): SearchResult
    {
        $query = BookRecord::query()->whereNull('deleted_at');

        if ($text !== null && $text !== '') {
            $like = '%'.addcslashes($text, '%_\\').'%';
            $query->where(fn ($q) => $q->whereRaw('title ILIKE ?', [$like])->orWhereRaw('author ILIKE ?', [$like]));
        }

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('title')->forPage($page, $size)->get();

        return new SearchResult(
            $rows->map(fn (BookRecord $record) => $this->toDomain($record))->all(),
            $page,
            $size,
            $total,
        );
    }

    private function toDomain(BookRecord $record): Book
    {
        return Book::reconstitute(
            $record->id,
            $record->title,
            $record->author,
            $record->isbn,
            $record->category_id,
            (int) $record->total_copies,
            (int) $record->available_copies,
            (int) $record->version,
            $record->cover_key,
            $record->deleted_at,
        );
    }
}
