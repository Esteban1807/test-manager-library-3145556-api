<?php

namespace App\Domain\Loan;

// Calculated, never stored (spec.md CA-08.3, constitution Article VII)
enum LoanStatus: string
{
    case Open = 'open';
    case Overdue = 'overdue';
    case Closed = 'closed';
}
