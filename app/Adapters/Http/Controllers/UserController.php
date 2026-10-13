<?php

namespace App\Adapters\Http\Controllers;

use App\Application\Ports\IdGenerator;
use App\Application\UseCases\CreateLibrarianService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// POST /users (api-contract §3.2, admin only)
final class UserController
{
    public function store(Request $request, CreateLibrarianService $create, IdGenerator $ids): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
            'role' => ['required', 'string'],
        ]);

        $user = $create->execute($ids->next(), $data['username'], $data['password'], $data['role']);

        return response()->json([
            'id' => $user->id(),
            'username' => $user->username(),
            'role' => $user->role()->value,
        ], 201);
    }
}
