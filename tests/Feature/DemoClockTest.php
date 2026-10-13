<?php

namespace Tests\Feature;

use App\Adapters\System\SystemClock;
use Tests\TestCase;

// D-API-04: the demo clock is off by default and only honours the header when enabled
class DemoClockTest extends TestCase
{
    public function test_header_is_ignored_when_demo_clock_is_off(): void
    {
        config(['library.demo_clock' => false]);
        request()->headers->set('X-Demo-Now', '2020-01-01T00:00:00Z');

        $this->assertNotSame('2020', (new SystemClock())->now()->format('Y'));
    }

    public function test_header_sets_now_when_demo_clock_is_on(): void
    {
        config(['library.demo_clock' => true]);
        request()->headers->set('X-Demo-Now', '2020-01-01T00:00:00Z');

        $this->assertSame('2020-01-01T00:00:00+00:00', (new SystemClock())->now()->format(DATE_ATOM));
    }
}
