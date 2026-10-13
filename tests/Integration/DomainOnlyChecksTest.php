<?php

namespace Tests\Integration;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\ResetsLibrarySchema;
use Tests\TestCase;

// T-18: invariants that only the domain enforced are now enforced by the database (Article VI)
class DomainOnlyChecksTest extends TestCase
{
    use ResetsLibrarySchema;

    private const NOVELA = '11111111-1111-4111-8111-111111111111';

    public function test_a_book_with_zero_total_copies_is_refused_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        DB::table('library.book')->insert([
            'id' => '00000000-0000-4000-8000-000000000101',
            'title' => 'Sin ejemplares',
            'author' => 'Autor',
            'isbn' => '9780307474728',
            'category_id' => self::NOVELA,
            'total_copies' => 0,
            'available_copies' => 0,
            'version' => 0,
        ]);
    }

    public function test_an_unknown_role_is_refused_by_the_database(): void
    {
        $this->expectException(QueryException::class);

        DB::table('library.user')->insert([
            'id' => '00000000-0000-4000-8000-000000000011',
            'username' => 'intruso',
            'password_hash' => 'hash',
            'role' => 'superuser',
        ]);
    }
}
