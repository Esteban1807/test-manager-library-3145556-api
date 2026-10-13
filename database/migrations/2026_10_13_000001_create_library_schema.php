<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Initial schema for Manager Library (data-model.md §3, §4, §9).
// No column has a DEFAULT: the domain sets every value (Article V, data-model §3).
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS library');

        Schema::create('library.category', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 120)->unique('uq_category_name');
        });

        Schema::create('library.book', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 250);
            $table->string('author', 200);
            $table->char('isbn', 13);
            $table->uuid('category_id');
            $table->integer('total_copies');
            $table->integer('available_copies');
            $table->integer('version'); // ADR-002 / plan D-04: optimistic concurrency token
            $table->string('cover_key', 512)->nullable();
            $table->timestampTz('deleted_at')->nullable();

            $table->foreign('category_id', 'fk_book_category')
                ->references('id')->on('library.category')->restrictOnDelete();
        });

        DB::statement('CREATE UNIQUE INDEX uq_book_isbn_active ON library.book (isbn) WHERE deleted_at IS NULL');
        DB::statement('CREATE INDEX ix_book_category_title_active ON library.book (category_id, title) WHERE deleted_at IS NULL');
        DB::statement('ALTER TABLE library.book ADD CONSTRAINT ck_book_available_range CHECK (available_copies >= 0 AND available_copies <= total_copies)');

        Schema::create('library.member', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name', 200);
            $table->string('document_number', 30)->unique('uq_member_document');
        });

        Schema::create('library.user', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username', 120)->unique('uq_user_username');
            $table->string('password_hash', 512);
            $table->string('role', 40);
        });

        Schema::create('library.loan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('member_id');
            $table->uuid('loaned_by_user_id');
            $table->timestampTz('loaned_at');

            $table->foreign('member_id', 'fk_loan_member')
                ->references('id')->on('library.member')->restrictOnDelete();
            $table->foreign('loaned_by_user_id', 'fk_loan_user')
                ->references('id')->on('library.user')->restrictOnDelete();
            $table->index('loaned_at', 'ix_loan_loaned_at');
        });

        Schema::create('library.loan_item', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('book_id');
            $table->string('book_title', 250);
            $table->char('isbn', 13);
            $table->string('category_name', 120); // frozen, no FK on purpose (ADR-004)
            $table->timestampTz('due_at');
            $table->timestampTz('returned_at')->nullable();

            $table->foreign('loan_id', 'fk_loan_item_loan')
                ->references('id')->on('library.loan')->cascadeOnDelete();
            $table->foreign('book_id', 'fk_loan_item_book')
                ->references('id')->on('library.book')->restrictOnDelete();
            $table->unique(['loan_id', 'book_id'], 'uq_loan_item_loan_book');
            $table->index('book_id', 'ix_loan_item_book');
        });

        DB::statement('CREATE INDEX ix_loan_item_overdue ON library.loan_item (due_at) WHERE returned_at IS NULL');

        // Fixed category ids from data-model §9
        DB::table('library.category')->insert([
            ['id' => '11111111-1111-4111-8111-111111111111', 'name' => 'Novela'],
            ['id' => '22222222-2222-4222-8222-222222222222', 'name' => 'Ciencia'],
            ['id' => '33333333-3333-4333-8333-333333333333', 'name' => 'Tecnología'],
            ['id' => '44444444-4444-4444-8444-444444444444', 'name' => 'Historia'],
            ['id' => '55555555-5555-4555-8555-555555555555', 'name' => 'Infantil'],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('library.loan_item');
        Schema::dropIfExists('library.loan');
        Schema::dropIfExists('library.user');
        Schema::dropIfExists('library.member');
        Schema::dropIfExists('library.book');
        Schema::dropIfExists('library.category');
    }
};
