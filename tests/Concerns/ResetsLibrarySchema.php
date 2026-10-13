<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\DB;

// Test-only reset of the PostgreSQL database.
// migrate:fresh drops only the public schema, so the library schema must be dropped here,
// otherwise its migration fails with "relation already exists" on the second test class.
// Runs against the test database (DB_DATABASE=library_test in phpunit.xml) only.
trait ResetsLibrarySchema
{
    protected function setUpResetsLibrarySchema(): void
    {
        DB::statement('DROP SCHEMA IF EXISTS library CASCADE');
        $this->artisan('migrate:fresh');
    }
}
