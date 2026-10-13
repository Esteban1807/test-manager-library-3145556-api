<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\ReportReader;
use App\Application\Ports\ReportRow;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

// One aggregate query over the FROZEN columns of loan lines (ADR-004). Nothing from the live catalog.
final class EloquentReportReader implements ReportReader
{
    public function mostBorrowed(DateTimeImmutable $from, DateTimeImmutable $to): array
    {
        return DB::table('library.loan_item as li')
            ->join('library.loan as l', 'l.id', '=', 'li.loan_id')
            ->whereBetween('l.loaned_at', [$from, $to])
            ->groupBy('li.book_id', 'li.book_title', 'li.category_name')
            ->selectRaw('li.book_id, li.book_title, li.category_name, count(*) as times')
            ->orderByDesc('times')
            ->orderBy('li.book_title')
            ->get()
            ->map(fn ($row) => new ReportRow($row->book_id, $row->book_title, $row->category_name, (int) $row->times))
            ->all();
    }
}
