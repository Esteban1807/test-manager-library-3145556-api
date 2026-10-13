<?php

namespace App\Adapters\Http\Controllers;

use App\Application\Ports\CategoryReader;
use Illuminate\Http\JsonResponse;

// GET /categories (api-contract §3.3): the five fixed categories
final class CategoryController
{
    public function index(CategoryReader $categories): JsonResponse
    {
        return response()->json(['items' => $categories->all()]);
    }
}
