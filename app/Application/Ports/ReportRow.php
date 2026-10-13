<?php

namespace App\Application\Ports;

// One row of the most-borrowed report, grouped by the FROZEN title and category (ADR-004, CA-10.1).
// No member and no librarian appears here (CA-10.3, data-model §7).
final readonly class ReportRow
{
    public function __construct(
        public string $bookId,
        public string $bookTitle,
        public string $categoryName,
        public int $timesBorrowed,
    ) {}
}
