<?php

namespace Tests\Integration;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_exactly_six_tables_exist_in_library_schema(): void
    {
        $names = DB::table('information_schema.tables')
            ->where('table_schema', 'library')
            ->orderBy('table_name')
            ->pluck('table_name')
            ->all();

        $this->assertSame(['book', 'category', 'loan', 'loan_item', 'member', 'user'], $names);
    }

    public function test_no_column_has_a_default(): void
    {
        $count = DB::table('information_schema.columns')
            ->where('table_schema', 'library')
            ->whereNotNull('column_default')
            ->count();

        $this->assertSame(0, $count);
    }

    public function test_available_range_check_constraint_exists(): void
    {
        $count = DB::table('pg_constraint')
            ->where('conname', 'ck_book_available_range')
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_five_fixed_categories_are_seeded(): void
    {
        $this->assertSame(5, DB::table('library.category')->count());
    }
}
