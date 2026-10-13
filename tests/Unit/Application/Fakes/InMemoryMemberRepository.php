<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\MemberRepository;
use App\Application\Ports\SearchResult;
use App\Domain\Member\Member;

final class InMemoryMemberRepository implements MemberRepository
{
    /** @var array<string, Member> */
    public array $members = [];

    public function findById(string $id): ?Member
    {
        return $this->members[$id] ?? null;
    }

    public function findByDocumentNumber(string $documentNumber): ?Member
    {
        foreach ($this->members as $member) {
            if ($member->documentNumber() === $documentNumber) {
                return $member;
            }
        }

        return null;
    }

    public function search(?string $text, int $page, int $size): SearchResult
    {
        $matches = array_values(array_filter($this->members, function (Member $member) use ($text): bool {
            if ($text === null || $text === '') {
                return true;
            }

            $needle = mb_strtolower($text);

            return str_contains(mb_strtolower($member->fullName()), $needle)
                || str_contains(mb_strtolower($member->documentNumber()), $needle);
        }));

        return new SearchResult(array_slice($matches, ($page - 1) * $size, $size), $page, $size, count($matches));
    }

    public function save(Member $member): void
    {
        $this->members[$member->id()] = $member;
    }
}
