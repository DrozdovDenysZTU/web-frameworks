<?php

namespace App\Http\Controllers;

use App\Models\Participant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ParticipantController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Participant::withCount('registrations')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:180', 'unique:participants,email'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        return response()->json(Participant::create($data), 201);
    }

    public function show(Participant $participant): JsonResponse
    {
        return response()->json($participant->load('events'));
    }

    public function update(Request $request, Participant $participant): JsonResponse
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => ['sometimes', 'required', 'email', 'max:180', Rule::unique('participants', 'email')->ignore($participant)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $participant->update($data);

        return response()->json($participant);
    }

    public function destroy(Participant $participant): JsonResponse
    {
        return $this->destroyModel($participant);
    }
}
