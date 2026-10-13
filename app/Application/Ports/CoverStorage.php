<?php

namespace App\Application\Ports;

// Port: cover binaries live outside the database (plan D-08, ADR notes in data-model §7)
interface CoverStorage
{
    // Returns the opaque key stored in books.cover_key
    public function store(string $bookId, string $bytes, string $mimeType): string;

    public function delete(string $key): void;
}
