<?php

namespace App\Adapters\Storage;

use App\Application\Ports\CoverStorage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

// Cover binaries on the private local disk. The key is opaque: a random name, never a path from the client.
final class LocalCoverStorage implements CoverStorage
{
    private const DIRECTORY = 'covers';

    public function store(string $bookId, string $bytes, string $mimeType): string
    {
        $extension = $mimeType === 'image/png' ? 'png' : 'jpg';
        $key = Str::uuid()->toString().'.'.$extension;

        Storage::disk('local')->put(self::DIRECTORY.'/'.$key, $bytes);

        return $key;
    }

    public function read(string $key): string
    {
        return Storage::disk('local')->get(self::DIRECTORY.'/'.$key);
    }

    public function delete(string $key): void
    {
        Storage::disk('local')->delete(self::DIRECTORY.'/'.$key);
    }
}
