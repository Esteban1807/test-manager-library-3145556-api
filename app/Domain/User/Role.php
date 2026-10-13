<?php

namespace App\Domain\User;

// Internal operator roles (spec.md §1). Only `admin` is provisioned by the deployment.
enum Role: string
{
    case Admin = 'admin';
    case Librarian = 'librarian';
}
