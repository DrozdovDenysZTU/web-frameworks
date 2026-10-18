<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    private const RELATIONS = ['category', 'venue', 'organizer'];

    public function index(Request $request): JsonResponse
    {
        $query = Event::with(self::RELATIONS)
            ->withCount('registrations')
            ->orderBy('start_at')
            ->orderBy('id');

        return $this->paginate($query, $request, [
            'id' => self::FILTER_INT,
            'title' => self::FILTER_LIKE,
            'description' => self::FILTER_LIKE,
            'start_at' => self::FILTER_DATE,
            'end_at' => self::FILTER_DATE,
            'price' => self::FILTER_NUMBER,
            'category_id' => self::FILTER_INT,
            'venue_id' => self::FILTER_INT,
            'organizer_id' => self::FILTER_INT,
            'created_at' => self::FILTER_DATE,
            'updated_at' => self::FILTER_DATE,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'venue_id' => ['required', 'integer', 'exists:venues,id'],
            'organizer_id' => ['required', 'integer', 'exists:organizers,id'],
        ]);

        $event = Event::create($data);

        return response()->json($event->load(self::RELATIONS), 201);
    }

    public function show(Event $event): JsonResponse
    {
        return response()->json($event->load([...self::RELATIONS, 'participants']));
    }

    public function update(Request $request, Event $event): JsonResponse
    {
        $startAt = $request->input('start_at', $event->start_at?->toDateTimeString());

        $data = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'start_at' => ['sometimes', 'required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:' . $startAt],
            'price' => ['sometimes', 'numeric', 'min:0'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'venue_id' => ['sometimes', 'required', 'integer', 'exists:venues,id'],
            'organizer_id' => ['sometimes', 'required', 'integer', 'exists:organizers,id'],
        ]);

        $event->update($data);

        return response()->json($event->load(self::RELATIONS));
    }

    public function destroy(Event $event): JsonResponse
    {
        return $this->destroyModel($event);
    }
}
