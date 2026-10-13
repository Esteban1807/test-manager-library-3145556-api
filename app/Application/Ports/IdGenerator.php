<?php

namespace App\Application\Ports;

// Port: identifiers are generated outside the domain (UUID v4, api-contract §1)
interface IdGenerator
{
    public function next(): string;
}
