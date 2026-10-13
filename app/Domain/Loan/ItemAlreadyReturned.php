<?php

namespace App\Domain\Loan;

use DomainException;

// api-contract ITEM_ALREADY_RETURNED (409), CA-07.2
final class ItemAlreadyReturned extends DomainException {}
