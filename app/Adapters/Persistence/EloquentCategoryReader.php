<?php

namespace App\Adapters\Persistence;

use App\Adapters\Persistence\Models\CategoryRecord;
use App\Application\Ports\CategoryReader;

final class EloquentCategoryReader implements CategoryReader
{
    public function nameOf(string $categoryId): ?string
    {
        return CategoryRecord::query()->find($categoryId)?->name;
    }

    public function all(): array
    {
        return CategoryRecord::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (CategoryRecord $record) => ['id' => $record->id, 'name' => $record->name])
            ->all();
    }
}
