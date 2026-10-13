<?php

namespace App\Adapters\Persistence;

use App\Adapters\Persistence\Models\LoanItemRecord;
use App\Adapters\Persistence\Models\LoanRecord;
use App\Application\Ports\LoanRepository;
use App\Application\Ports\SearchResult;
use App\Domain\Book\BookNotAvailable;
use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanItem;
use App\Domain\Loan\LoanStatus;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

// Saves a loan and the touched books in ONE transaction (ADR-002).
// The book update is guarded by the version token: if another operation changed the book
// first, zero rows change and the whole transaction is rolled back (409 BOOK_NOT_AVAILABLE).
final class EloquentLoanRepository implements LoanRepository
{
    public function findById(string $id): ?Loan
    {
        $record = LoanRecord::query()->with('items')->find($id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findWithOpenItems(): array
    {
        return LoanRecord::query()
            ->with('items')
            ->whereHas('items', fn ($q) => $q->whereNull('returned_at'))
            ->get()
            ->map(fn (LoanRecord $record) => $this->toDomain($record))
            ->all();
    }

    public function save(Loan $loan, array $books): void
    {
        DB::transaction(function () use ($loan, $books): void {
            LoanRecord::query()->updateOrCreate(['id' => $loan->id()], [
                'member_id' => $loan->memberId(),
                'loaned_by_user_id' => $loan->loanedByUserId(),
                'loaned_at' => $loan->loanedAt(),
            ]);

            foreach ($loan->items() as $item) {
                LoanItemRecord::query()->updateOrCreate(['id' => $item->id()], [
                    'loan_id' => $loan->id(),
                    'book_id' => $item->bookId(),
                    'book_title' => $item->bookTitle(),
                    'isbn' => $item->isbn(),
                    'category_name' => $item->categoryName(),
                    'due_at' => $item->dueAt(),
                    'returned_at' => $item->returnedAt(),
                ]);
            }

            foreach ($books as $book) {
                // The domain bumps the version once per operation, so the stored version is one behind
                $changed = DB::table('library.book')
                    ->where('id', $book->id())
                    ->where('version', $book->version() - 1)
                    ->update([
                        'available_copies' => $book->available(),
                        'version' => $book->version(),
                    ]);

                if ($changed !== 1) {
                    throw new BookNotAvailable('El ejemplar cambió mientras se registraba el préstamo.');
                }
            }
        });
    }

    public function countOpenItemsForMember(string $memberId): int
    {
        return DB::table('library.loan_item as li')
            ->join('library.loan as l', 'l.id', '=', 'li.loan_id')
            ->where('l.member_id', $memberId)
            ->whereNull('li.returned_at')
            ->count();
    }

    public function countOverdueItemsForMember(string $memberId, DateTimeImmutable $now): int
    {
        return DB::table('library.loan_item as li')
            ->join('library.loan as l', 'l.id', '=', 'li.loan_id')
            ->where('l.member_id', $memberId)
            ->whereNull('li.returned_at')
            ->where('li.due_at', '<', $now)
            ->count();
    }

    public function search(
        ?DateTimeImmutable $from,
        ?DateTimeImmutable $to,
        ?LoanStatus $status,
        ?string $memberId,
        int $page,
        int $size,
        DateTimeImmutable $now,
    ): SearchResult {
        $query = LoanRecord::query();

        if ($from !== null) {
            $query->where('loaned_at', '>=', $from);
        }

        if ($to !== null) {
            $query->where('loaned_at', '<=', $to);
        }

        if ($memberId !== null) {
            $query->where('member_id', $memberId);
        }

        // Status is calculated, never stored (CA-08.3): same rules as Loan::status()
        if ($status === LoanStatus::Closed) {
            $query->whereDoesntHave('items', fn ($q) => $q->whereNull('returned_at'));
        } elseif ($status === LoanStatus::Overdue) {
            $query->whereHas('items', fn ($q) => $q->whereNull('returned_at')->where('due_at', '<', $now));
        } elseif ($status === LoanStatus::Open) {
            $query->whereHas('items', fn ($q) => $q->whereNull('returned_at'))
                ->whereDoesntHave('items', fn ($q) => $q->whereNull('returned_at')->where('due_at', '<', $now));
        }

        $total = (clone $query)->count();
        $rows = $query->with('items')->orderByDesc('loaned_at')->forPage($page, $size)->get();

        return new SearchResult(
            $rows->map(fn (LoanRecord $record) => $this->toDomain($record))->all(),
            $page,
            $size,
            $total,
        );
    }

    private function toDomain(LoanRecord $record): Loan
    {
        $items = $record->items
            ->map(fn (LoanItemRecord $item) => new LoanItem(
                $item->id,
                $item->book_id,
                $item->book_title,
                $item->isbn,
                $item->category_name,
                $item->due_at,
                $item->returned_at,
            ))
            ->all();

        return Loan::reconstitute(
            $record->id,
            $record->member_id,
            $record->loaned_by_user_id,
            $record->loaned_at,
            $items,
        );
    }
}
