<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Presenter;
use App\Application\UseCases\MostBorrowedReportService;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// GET /reports/most-borrowed (api-contract §3.7). Admin only (enforced by routes).
final class ReportController
{
    public function mostBorrowed(Request $request, MostBorrowedReportService $report): JsonResponse
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        $utc = new DateTimeZone('UTC');
        $from = new DateTimeImmutable($data['from'].' 00:00:00', $utc);
        $to = new DateTimeImmutable($data['to'].' 23:59:59', $utc);

        $rows = $report->execute($from, $to);

        return response()->json([
            'from' => $data['from'],
            'to' => $data['to'],
            'rows' => array_map(Presenter::reportRow(...), $rows),
        ]);
    }
}
