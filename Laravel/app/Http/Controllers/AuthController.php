<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:4096'],
        ]);

        $user = User::create([...$data, 'role' => User::ROLE_CLIENT]);

        return response()->json($user, 201);
    }

    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $token = auth('api')->attempt($credentials);

        if (!$token) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        return response()->json(['token' => $token]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
