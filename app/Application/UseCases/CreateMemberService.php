<?php

namespace App\Application\UseCases;

use App\Application\Errors\DocumentTaken;
use App\Application\Ports\MemberRepository;
use App\Domain\Member\DocumentNumber;
use App\Domain\Member\Member;

// HU-05: admin or librarian registers a borrower (CA-05.1). Members are never deleted (CA-05.3).
final class CreateMemberService
{
    public function __construct(private readonly MemberRepository $members) {}

    public function execute(string $id, string $fullName, string $documentNumber): Member
    {
        $document = DocumentNumber::fromString($documentNumber);

        if ($this->members->findByDocumentNumber($document->value) !== null) {
            throw new DocumentTaken();
        }

        $member = Member::create($id, $fullName, $document);

        $this->members->save($member);

        return $member;
    }
}
