<?php

namespace App\Application\Ports;

use App\Domain\Member\Member;

// Port: borrowers. Implemented by Adapters/Persistence.
interface MemberRepository
{
    public function findById(string $id): ?Member;

    public function save(Member $member): void;
}
