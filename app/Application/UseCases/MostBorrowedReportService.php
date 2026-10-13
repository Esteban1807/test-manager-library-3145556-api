<?php

namespace App\Application\UseCases;

use App\Application\Errors\InvalidDateRange;
use App\Application\Ports\ReportReader;
use App\Application\Ports\ReportRow;
use DateTimeImmutable;

// HU-10: most borrowed books in a date range (CA-10.1 to CA-10.5). Admin only (enforced by HTTP).
final class MostBorrowedReportService
{
    public function __construct(private readonly ReportReader $reader) {}

    /** @return ReportRow[] */
    public function execute(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        if ($to < $from) {
            throw new InvalidDateRange();
        }

        return $this->reader->mostBorrowed($from, $to);
    }
}
