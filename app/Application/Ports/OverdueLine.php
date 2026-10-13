<?php

namespace App\Application\Ports;

use DateTimeImmutable;

// One row of the overdue list (api-contract §3.6 GET /loans/overdue, CA-09.1).
// Shows the member's name because it is an operational list, not the report (HU-10 / CA-10.3).
final readonly class OverdueLine
{
    public function __construct(
        public string $loanId,
        public string $itemId,
        public string $bookTitle,
        public string $memberName,
        public DateTimeImmutable $dueAt,
        public int $daysLate,
    ) {}
}
