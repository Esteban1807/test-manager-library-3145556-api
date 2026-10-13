<?php

namespace App\Application\Ports;

use App\Domain\Book\Book;

// Page of results with the total (CA-04.2)
final readonly class SearchResult
{
    /** @param Book[] $items */
    public function __construct(
        public array $items,
        public int $page,
        public int $size,
        public int $total,
    ) {}
}
