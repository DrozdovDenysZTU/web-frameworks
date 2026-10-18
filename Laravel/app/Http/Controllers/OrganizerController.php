<?php

namespace App\Http\Controllers;

use App\Models\Organizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrganizerController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return $this->paginate(Organizer::withCount('events')->orderBy('id'), $request, [
            'id' => self::FILTER_INT,
            'name' => self::FILTER_LIKE,
            'email' => self::FILTER_LIKE,
            'phone' => self::FILTER_LIKE,
            'created_at' => self::FILTER_DATE,
            'updated_at' => self::FILTER_DATE,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', 'unique:organizers,email'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        return response()->json(Organizer::create($data), 201);
    }

    public function show(Organizer $organizer): JsonResponse
    {
        return response()->json($organizer->load('events'));
    }

    public function update(Request $request, Organizer $organizer): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'email' => ['sometimes', 'required', 'email', 'max:180', Rule::unique('organizers', 'email')->ignore($organizer)],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $organizer->update($data);

        return response()->json($organizer);
    }

    public function destroy(Organizer $organizer): JsonResponse
    {
        return $this->destroyModel($organizer);
    }
}
