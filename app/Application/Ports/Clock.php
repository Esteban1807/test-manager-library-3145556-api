<?php

namespace App\Application\Ports;

use DateTimeImmutable;

// Port: the only source of "now" for the application (plan D-05, RN-6 UTC)
interface Clock
{
    public function now(): DateTimeImmutable;
}
