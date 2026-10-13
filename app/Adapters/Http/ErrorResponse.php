<?php

namespace App\Adapters\Http;

use App\Adapters\Http\Errors\ForbiddenRole;
use App\Adapters\Http\Errors\Unauthenticated;
use App\Application\Errors\BookHasOpenLoans;
use App\Application\Errors\BookNotFound;
use App\Application\Errors\DocumentTaken;
use App\Application\Errors\InvalidCredentials;
use App\Application\Errors\InvalidDateRange;
use App\Application\Errors\IsbnTaken;
use App\Application\Errors\LoanNotFound;
use App\Application\Errors\MemberNotFound;
use App\Application\Errors\UsernameTaken;
use App\Domain\Book\BookNotAvailable;
use App\Domain\Loan\DuplicateBookInLoan;
use App\Domain\Loan\ItemAlreadyReturned;
use App\Domain\Loan\MemberHasOverdue;
use App\Domain\Loan\MemberLimitExceeded;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

// The ONLY place that turns exceptions into the single error body of api-contract §1.
// Internals (traces, SQL, paths) never leave the server.
final class ErrorResponse
{
    /** @var array<class-string, array{int, string}> */
    private const MAPPED = [
        InvalidCredentials::class => [401, 'INVALID_CREDENTIALS'],
        Unauthenticated::class => [401, 'UNAUTHENTICATED'],
        ForbiddenRole::class => [403, 'FORBIDDEN_ROLE'],
        BookNotFound::class => [404, 'NOT_FOUND'],
        MemberNotFound::class => [404, 'NOT_FOUND'],
        LoanNotFound::class => [404, 'NOT_FOUND'],
        NotFoundHttpException::class => [404, 'NOT_FOUND'],
        UsernameTaken::class => [409, 'USERNAME_TAKEN'],
        IsbnTaken::class => [409, 'ISBN_TAKEN'],
        DocumentTaken::class => [409, 'DOCUMENT_TAKEN'],
        BookHasOpenLoans::class => [409, 'BOOK_HAS_OPEN_LOANS'],
        BookNotAvailable::class => [409, 'BOOK_NOT_AVAILABLE'],
        MemberHasOverdue::class => [409, 'MEMBER_HAS_OVERDUE'],
        ItemAlreadyReturned::class => [409, 'ITEM_ALREADY_RETURNED'],
        MemberLimitExceeded::class => [422, 'MEMBER_LIMIT_EXCEEDED'],
        DuplicateBookInLoan::class => [422, 'DUPLICATE_BOOK_IN_LOAN'],
        InvalidDateRange::class => [422, 'INVALID_DATE_RANGE'],
    ];

    public static function fromThrowable(Throwable $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return self::body(422, 'VALIDATION_ERROR', 'Hay campos inválidos.', self::details($e));
        }

        foreach (self::MAPPED as $class => [$status, $code]) {
            if ($e instanceof $class) {
                return self::body($status, $code, $e->getMessage());
            }
        }

        // Domain and input rules that have no dedicated code (api-contract VALIDATION_ERROR, 422)
        if ($e instanceof InvalidArgumentException || $e instanceof DomainException) {
            return self::body(422, 'VALIDATION_ERROR', $e->getMessage());
        }

        return self::body(500, 'INTERNAL_ERROR', 'Error interno del servidor.');
    }

    private static function body(int $status, string $code, string $message, array $details = []): JsonResponse
    {
        return response()->json([
            'code' => $code,
            'message' => $message,
            'details' => $details,
        ], $status);
    }

    /** @return array<int, array{field: string, issue: string}> */
    private static function details(ValidationException $e): array
    {
        $details = [];

        foreach ($e->errors() as $field => $messages) {
            $details[] = ['field' => $field, 'issue' => $messages[0]];
        }

        return $details;
    }
}
