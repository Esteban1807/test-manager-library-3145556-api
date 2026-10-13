<?php

namespace App\Adapters\System;

use App\Application\Ports\Clock;
use DateTimeImmutable;
use DateTimeZone;
use Throwable;

// Real clock, always UTC (RN-6).
// Demo mode (LIBRARY_DEMO_CLOCK=true, off by default): the X-Demo-Now header replaces "now"
// for that request, so the seeder in `tool` can create overdue loans through the API (D-API-04).
final class SystemClock implements Clock
{
    public function now(): DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');

        if (config('library.demo_clock') === true) {
            $header = request()->header('X-Demo-Now');

            if (is_string($header) && $header !== '') {
                try {
                    return (new DateTimeImmutable($header))->setTimezone($utc);
                } catch (Throwable) {
                    // An unreadable header falls back to the real clock
                }
            }
        }

        return new DateTimeImmutable('now', $utc);
    }
}
