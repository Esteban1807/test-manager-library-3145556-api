<?php

namespace App\Domain\Loan;

use DomainException;

// api-contract MEMBER_LIMIT_EXCEEDED (422), CA-06.4, RN-2
final class MemberLimitExceeded extends DomainException {}
