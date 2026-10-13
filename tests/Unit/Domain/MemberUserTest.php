<?php

namespace Tests\Unit\Domain;

use App\Domain\Member\DocumentNumber;
use App\Domain\Member\Member;
use App\Domain\User\Role;
use App\Domain\User\User;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class MemberUserTest extends TestCase
{
    public function test_username_is_trimmed_and_lowercased(): void
    {
        $user = User::registerFromApi('u-1', '  Ana  ', 'hash', 'librarian');

        $this->assertSame('ana', $user->username());
        $this->assertSame(Role::Librarian, $user->role());
    }

    public function test_api_rejects_admin_role(): void
    {
        $this->expectException(InvalidArgumentException::class);
        User::registerFromApi('u-1', 'ana', 'hash', 'admin');
    }

    public function test_deployment_can_provision_admin(): void
    {
        $user = User::provision('u-0', 'admin', 'hash', Role::Admin);

        $this->assertSame(Role::Admin, $user->role());
    }

    public function test_member_name_is_trimmed(): void
    {
        $member = Member::create('m-1', '  Luis Pérez ', DocumentNumber::fromString('12345'));

        $this->assertSame('Luis Pérez', $member->fullName());
        $this->assertSame('12345', $member->documentNumber());
    }

    public function test_member_requires_a_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Member::create('m-1', '   ', DocumentNumber::fromString('12345'));
    }
}
