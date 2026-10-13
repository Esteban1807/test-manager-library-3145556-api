<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\Clock;
use DateTimeImmutable;

final class FixedClock implements Clock
{
    public function __construct(private readonly DateTimeImmutable $at) {}

    public function now(): DateTimeImmutable
    {
        return $this->at;
    }
}
