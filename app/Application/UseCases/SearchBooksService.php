<?php

namespace App\Application\UseCases;

use App\Application\Ports\BookRepository;
use App\Application\Ports\SearchResult;
use InvalidArgumentException;

// HU-04: search the active catalog (CA-04.1, CA-04.2). Page size is capped at 50.
final class SearchBooksService
{
    private const MAX_SIZE = 50;

    public function __construct(private readonly BookRepository $books) {}

    public function execute(?string $text, ?string $categoryId, int $page, int $size): SearchResult
    {
        if ($page < 1 || $size < 1 || $size > self::MAX_SIZE) {
            throw new InvalidArgumentException('La página debe ser ≥ 1 y el tamaño entre 1 y 50.');
        }

        return $this->books->search($text, $categoryId, $page, $size);
    }
}
