<?php

namespace App\Adapters\Http;

use App\Application\Ports\OverdueLine;
use App\Application\Ports\ReportRow;
use App\Domain\Book\Book;
use App\Domain\Loan\Loan;
use App\Domain\Loan\LoanItem;
use App\Domain\Member\Member;
use DateTimeImmutable;

// Turns domain objects into the JSON shapes of api-contract §3.
// Never includes password_hash, the token secret or anything personal outside its endpoint.
final class Presenter
{
    /** @return array<string, mixed> */
    public static function book(Book $book, string $categoryName): array
    {
        return [
            'id' => $book->id(),
            'title' => $book->title(),
            'author' => $book->author(),
            'isbn' => $book->isbn(),
            'category' => ['id' => $book->categoryId(), 'name' => $categoryName],
            'total_copies' => $book->total(),
            'available_copies' => $book->available(),
            'cover_key' => $book->coverKey(),
        ];
    }

    /** @return array<string, mixed> */
    public static function member(Member $member): array
    {
        return [
            'id' => $member->id(),
            'full_name' => $member->fullName(),
            'document_number' => $member->documentNumber(),
        ];
    }

    /** @return array<string, mixed> */
    public static function loan(Loan $loan, \App\Domain\Loan\LoanStatus $status): array
    {
        return [
            'id' => $loan->id(),
            'member_id' => $loan->memberId(),
            'loaned_at' => self::date($loan->loanedAt()),
            'status' => $status->value,
            'items' => array_map(self::item(...), $loan->items()),
        ];
    }

    /** @return array<string, mixed> */
    public static function item(LoanItem $item): array
    {
        return [
            'id' => $item->id(),
            'book_id' => $item->bookId(),
            'book_title' => $item->bookTitle(),
            'isbn' => $item->isbn(),
            'category_name' => $item->categoryName(),
            'due_at' => self::date($item->dueAt()),
            'returned_at' => $item->returnedAt() ? self::date($item->returnedAt()) : null,
        ];
    }

    /** @return array<string, mixed> */
    public static function overdue(OverdueLine $line): array
    {
        return [
            'loan_id' => $line->loanId,
            'item_id' => $line->itemId,
            'book_title' => $line->bookTitle,
            'member_name' => $line->memberName,
            'due_at' => self::date($line->dueAt),
            'days_late' => $line->daysLate,
        ];
    }

    /** @return array<string, mixed> */
    public static function reportRow(ReportRow $row): array
    {
        return [
            'book_id' => $row->bookId,
            'book_title' => $row->bookTitle,
            'category_name' => $row->categoryName,
            'times_borrowed' => $row->timesBorrowed,
        ];
    }

    // ISO 8601 in UTC with Z (api-contract §1)
    public static function date(DateTimeImmutable $at): string
    {
        return $at->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }
}
