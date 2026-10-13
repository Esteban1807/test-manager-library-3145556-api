<?php

namespace App\Adapters\Persistence;

use App\Application\Ports\OpenLoansReader;
use Illuminate\Support\Facades\DB;

final class EloquentOpenLoansReader implements OpenLoansReader
{
    public function bookHasOpenLoans(string $bookId): bool
    {
        return DB::table('library.loan_item')
            ->where('book_id', $bookId)
            ->whereNull('returned_at')
            ->exists();
    }
}
