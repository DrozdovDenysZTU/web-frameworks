<?php

namespace App\Http\Controllers;

use App\Models\Venue;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(Venue::withCount('events')->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json(Venue::create($data), 201);
    }

    public function show(Venue $venue): JsonResponse
    {
        return response()->json($venue->load('events'));
    }

    public function update(Request $request, Venue $venue): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'address' => ['sometimes', 'required', 'string', 'max:255'],
            'city' => ['sometimes', 'required', 'string', 'max:100'],
            'capacity' => ['sometimes', 'required', 'integer', 'min:1'],
        ]);

        $venue->update($data);

        return response()->json($venue);
    }

    public function destroy(Venue $venue): JsonResponse
    {
        return $this->destroyModel($venue);
    }
}
