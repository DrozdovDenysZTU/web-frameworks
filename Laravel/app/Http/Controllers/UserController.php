<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->paginate(User::query()->orderBy('id'), $request, [
            'id' => self::FILTER_INT,
            'name' => self::FILTER_LIKE,
            'email' => self::FILTER_LIKE,
            'role' => self::FILTER_EXACT,
            'created_at' => self::FILTER_DATE,
            'updated_at' => self::FILTER_DATE,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:4096'],
            'role' => ['sometimes', Rule::in(array_keys(User::ROLE_LEVELS))],
        ]);

        return response()->json(User::create($data), 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'max:4096'],
            'role' => ['sometimes', 'required', Rule::in(array_keys(User::ROLE_LEVELS))],
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->is($user)) {
            return response()->json(['message' => 'You cannot delete your own account.'], 409);
        }

        return $this->destroyModel($user);
    }
}
