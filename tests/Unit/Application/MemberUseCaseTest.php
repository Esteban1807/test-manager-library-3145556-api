<?php

namespace Tests\Unit\Application;

use App\Application\Errors\DocumentTaken;
use App\Application\UseCases\CreateMemberService;
use PHPUnit\Framework\TestCase;
use Tests\Unit\Application\Fakes\InMemoryMemberRepository;

class MemberUseCaseTest extends TestCase
{
    public function test_creates_member_and_finds_by_name_or_document(): void
    {
        $members = new InMemoryMemberRepository();
        (new CreateMemberService($members))->execute('m-1', 'Luis Pérez', '123');

        $byName = $members->search('luis', 1, 20);
        $byDocument = $members->search('123', 1, 20);

        $this->assertSame(1, $byName->total);
        $this->assertSame(1, $byDocument->total);
    }

    public function test_repeated_document_is_rejected(): void
    {
        $members = new InMemoryMemberRepository();
        $service = new CreateMemberService($members);
        $service->execute('m-1', 'Luis Pérez', '123');

        $this->expectException(DocumentTaken::class);
        $service->execute('m-2', 'Otra Persona', '123');
    }
}
