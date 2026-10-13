<?php

namespace App\Adapters\Http\Controllers;

use App\Adapters\Http\Presenter;
use App\Application\Errors\LoanNotFound;
use App\Application\Ports\Clock;
use App\Application\Ports\LoanRepository;
use App\Application\UseCases\LendBooksService;
use App\Application\UseCases\OverdueService;
use App\Application\UseCases\ReturnItemService;
use App\Domain\Loan\LoanStatus;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// /loans (api-contract §3.6). Admin and librarian only (enforced by routes).
final class LoanController
{
    public function store(Request $request, LendBooksService $lend, Clock $clock): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'string'],
            'items' => ['required', 'array', 'min:1', 'max:3'],
            'items.*.book_id' => ['required', 'string'],
        ]);

        $bookIds = array_column($data['items'], 'book_id');

        $loan = $lend->execute($data['member_id'], $request->attributes->get('user_id'), $bookIds);

        return response()->json(Presenter::loan($loan, $loan->status($clock->now())), 201);
    }

    public function index(Request $request, LoanRepository $loans, Clock $clock): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'in:open,overdue,closed'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
        ]);

        $now = $clock->now();
        $page = max(1, (int) $request->query('page', 1));
        $size = min(50, max(1, (int) $request->query('size', 20)));
        $status = $request->query('status') ? LoanStatus::from($request->query('status')) : null;

        $result = $loans->search(
            $this->startOfDay($request->query('from')),
            $this->endOfDay($request->query('to')),
            $status,
            $request->query('member_id'),
            $page,
            $size,
            $now,
        );

        return response()->json([
            'items' => array_map(fn ($loan) => Presenter::loan($loan, $loan->status($now)), $result->items),
            'page' => $result->page,
            'size' => $result->size,
            'total' => $result->total,
        ]);
    }

    public function show(string $id, LoanRepository $loans, Clock $clock): JsonResponse
    {
        $loan = $loans->findById($id);

        if ($loan === null) {
            throw new LoanNotFound();
        }

        return response()->json(Presenter::loan($loan, $loan->status($clock->now())));
    }

    public function returnItem(string $loanId, string $itemId, ReturnItemService $return): JsonResponse
    {
        $loan = $return->execute($loanId, $itemId);

        foreach ($loan->items() as $item) {
            if ($item->id() === $itemId) {
                return response()->json(Presenter::item($item));
            }
        }

        throw new LoanNotFound();
    }

    public function overdue(OverdueService $overdue): JsonResponse
    {
        return response()->json([
            'items' => array_map(Presenter::overdue(...), $overdue->execute()),
        ]);
    }

    private function startOfDay(?string $date): ?DateTimeImmutable
    {
        return $date === null ? null : new DateTimeImmutable($date.' 00:00:00', new DateTimeZone('UTC'));
    }

    // Inclusive end: the whole last day (CA-08.2, RN-6)
    private function endOfDay(?string $date): ?DateTimeImmutable
    {
        return $date === null ? null : new DateTimeImmutable($date.' 23:59:59', new DateTimeZone('UTC'));
    }
}
