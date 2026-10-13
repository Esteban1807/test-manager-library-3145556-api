<?php

namespace Tests\Unit\Application\Fakes;

use App\Application\Ports\CategoryReader;

final class FixedCategoryReader implements CategoryReader
{
    /** @var array<string, string> */
    public array $names = [
        '11111111-1111-4111-8111-111111111111' => 'Novela',
        '22222222-2222-4222-8222-222222222222' => 'Ciencia',
    ];

    public function nameOf(string $categoryId): ?string
    {
        return $this->names[$categoryId] ?? null;
    }
}
