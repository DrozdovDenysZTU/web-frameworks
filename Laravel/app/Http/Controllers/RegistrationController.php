<?php

namespace App\Http\Controllers;

use App\Models\Registration;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RegistrationController extends Controller
{
    private const RELATIONS = ['event', 'participant'];

    public function index(Request $request): JsonResponse
    {
        $registrations = Registration::with(self::RELATIONS)
            ->when($request->query('event_id'), fn ($query, $id) => $query->where('event_id', $id))
            ->when($request->query('participant_id'), fn ($query, $id) => $query->where('participant_id', $id))
            ->when($request->query('status'), fn ($query, $status) => $query->where('status', $status))
            ->get();

        return response()->json($registrations);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'participant_id' => [
                'required',
                'integer',
                'exists:participants,id',
                Rule::unique('registrations')->where('event_id', $request->input('event_id')),
            ],
            'status' => ['sometimes', Rule::in(Registration::STATUSES)],
        ]);

        $registration = Registration::create($data);

        return response()->json($registration->refresh()->load(self::RELATIONS), 201);
    }

    public function show(Registration $registration): JsonResponse
    {
        return response()->json($registration->load(self::RELATIONS));
    }

    public function update(Request $request, Registration $registration): JsonResponse
    {
        $eventId = $request->input('event_id', $registration->event_id);

        $data = $request->validate([
            'event_id' => ['sometimes', 'required', 'integer', 'exists:events,id'],
            'participant_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:participants,id',
                Rule::unique('registrations')->where('event_id', $eventId)->ignore($registration),
            ],
            'status' => ['sometimes', 'required', Rule::in(Registration::STATUSES)],
        ]);

        $registration->update($data);

        return response()->json($registration->load(self::RELATIONS));
    }

    public function destroy(Registration $registration): JsonResponse
    {
        return $this->destroyModel($registration);
    }
}
