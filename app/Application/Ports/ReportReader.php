<?php

namespace App\Application\Ports;

use DateTimeImmutable;

// Port: the report is computed by one aggregate query, never stored (ADR-004)
interface ReportReader
{
    /** @return ReportRow[] */
    public function mostBorrowed(DateTimeImmutable $from, DateTimeImmutable $to): array;
}
