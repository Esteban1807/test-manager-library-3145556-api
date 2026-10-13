<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\OpenLoansReader;

final class FakeOpenLoansReader implements OpenLoansReader
{
    /** @var string[] */
    public array $bookIdsWithOpenLoans = [];

    public function bookHasOpenLoans(string $bookId): bool
    {
        return in_array($bookId, $this->bookIdsWithOpenLoans, true);
    }
}
