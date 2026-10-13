<?php

namespace App\Domain\Loan;

use DomainException;

// api-contract DUPLICATE_BOOK_IN_LOAN (422), CA-06.2
final class DuplicateBookInLoan extends DomainException {}
