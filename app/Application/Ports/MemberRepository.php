<?php

namespace App\Application\Ports;

use App\Domain\Member\Member;

// Port: borrowers. Implemented by Adapters/Persistence.
interface MemberRepository
{
    public function findById(string $id): ?Member;

    public function save(Member $member): void;

    public function findByDocumentNumber(string $documentNumber): ?Member;

    // CA-05.2: partial match on name or document, paginated
    public function search(?string $text, int $page, int $size): SearchResult;
}
