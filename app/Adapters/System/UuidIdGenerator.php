<?php

namespace App\Adapters\System;

use App\Application\Ports\IdGenerator;
use Illuminate\Support\Str;

// UUID v4 identifiers (api-contract §1)
final class UuidIdGenerator implements IdGenerator
{
    public function next(): string
    {
        return Str::uuid()->toString();
    }
}
