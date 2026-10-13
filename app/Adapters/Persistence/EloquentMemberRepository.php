<?php

namespace App\Adapters\Persistence;

use App\Adapters\Persistence\Models\MemberRecord;
use App\Application\Ports\MemberRepository;
use App\Application\Ports\SearchResult;
use App\Domain\Member\Member;

final class EloquentMemberRepository implements MemberRepository
{
    public function findById(string $id): ?Member
    {
        $record = MemberRecord::query()->find($id);

        return $record ? $this->toDomain($record) : null;
    }

    public function findByDocumentNumber(string $documentNumber): ?Member
    {
        $record = MemberRecord::query()->where('document_number', $documentNumber)->first();

        return $record ? $this->toDomain($record) : null;
    }

    public function save(Member $member): void
    {
        MemberRecord::query()->updateOrCreate(['id' => $member->id()], [
            'full_name' => $member->fullName(),
            'document_number' => $member->documentNumber(),
        ]);
    }

    public function search(?string $text, int $page, int $size): SearchResult
    {
        $query = MemberRecord::query();

        if ($text !== null && $text !== '') {
            $like = '%'.addcslashes($text, '%_\\').'%';
            $query->where(fn ($q) => $q->whereRaw('full_name ILIKE ?', [$like])->orWhereRaw('document_number ILIKE ?', [$like]));
        }

        $total = (clone $query)->count();
        $rows = $query->orderBy('full_name')->forPage($page, $size)->get();

        return new SearchResult(
            $rows->map(fn (MemberRecord $record) => $this->toDomain($record))->all(),
            $page,
            $size,
            $total,
        );
    }

    private function toDomain(MemberRecord $record): Member
    {
        return Member::reconstitute($record->id, $record->full_name, $record->document_number);
    }
}
