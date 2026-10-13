<?php

namespace App\Adapters\Http\Controllers;

use App\Application\UseCases\LoginService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// POST /auth/login (api-contract §3.1)
final class AuthController
{
    public function login(Request $request, LoginService $login): JsonResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:120'],
            'password' => ['required', 'string'],
        ]);

        $token = $login->execute($data['username'], $data['password']);

        return response()->json([
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $token->expiresIn,
            'role' => $token->role,
        ]);
    }
}
