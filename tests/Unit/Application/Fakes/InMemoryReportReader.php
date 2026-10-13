<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\ReportReader;
use App\Application\Ports\ReportRow;
use App\Domain\Loan\Loan;
use DateTimeImmutable;

// Reads only the FROZEN fields of loan lines, like the real aggregate query does (ADR-004)
final class InMemoryReportReader implements ReportReader
{
    /** @param array<string, Loan> $loans */
    public function __construct(private readonly array $loans) {}

    public function mostBorrowed(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        $counts = [];

        foreach ($this->loans as $loan) {
            if ($loan->loanedAt() < $from || $loan->loanedAt() > $to) {
                continue;
            }

            foreach ($loan->items() as $item) {
                $key = $item->bookId().'|'.$item->bookTitle().'|'.$item->categoryName();
                $counts[$key] ??= ['bookId' => $item->bookId(), 'title' => $item->bookTitle(), 'category' => $item->categoryName(), 'times' => 0];
                $counts[$key]['times']++;
            }
        }

        usort($counts, fn ($a, $b) => [$b['times'], $a['title']] <=> [$a['times'], $b['title']]);

        return array_map(
            fn ($row) => new ReportRow($row['bookId'], $row['title'], $row['category'], $row['times']),
            $counts,
        );
    }
}
