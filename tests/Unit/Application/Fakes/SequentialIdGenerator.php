<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\IdGenerator;

// Deterministic ids for tests: id-1, id-2, ...
final class SequentialIdGenerator implements IdGenerator
{
    private int $counter = 0;

    public function next(): string
    {
        $this->counter++;

        return 'id-'.$this->counter;
    }
}
