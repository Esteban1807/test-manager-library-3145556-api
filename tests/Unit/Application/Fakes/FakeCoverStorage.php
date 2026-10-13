<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\CoverStorage;

final class FakeCoverStorage implements CoverStorage
{
    /** @var array<string, string> key => bytes */
    public array $stored = [];

    public function store(string $bookId, string $bytes, string $mimeType): string
    {
        $key = 'cover-'.$bookId.'-'.count($this->stored);
        $this->stored[$key] = $bytes;

        return $key;
    }

    public function read(string $key): string
    {
        return $this->stored[$key];
    }

    public function delete(string $key): void
    {
        unset($this->stored[$key]);
    }
}
