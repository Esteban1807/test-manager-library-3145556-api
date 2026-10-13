<?php

namespace App\Adapters\System;

use App\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;

// Real clock, always UTC (RN-6)
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }
}
