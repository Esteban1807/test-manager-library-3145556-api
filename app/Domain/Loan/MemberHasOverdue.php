<?php

namespace App\Domain\Loan;

use DomainException;

// api-contract MEMBER_HAS_OVERDUE (409), CA-06.5, RN-3
final class MemberHasOverdue extends DomainException {}
