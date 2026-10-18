<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Participant;
use App\Entity\Registration;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/registrations', name: 'api_registrations_')]
class RegistrationController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(RegistrationRepository $repository, Request $request): JsonResponse
    {
        $criteria = array_filter([
            'event' => $request->query->get('event_id'),
            'participant' => $request->query->get('participant_id'),
            'status' => $request->query->get('status'),
        ], static fn ($value) => $value !== null);

        return $this->list($repository->findBy($criteria));
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Registration $registration): JsonResponse
    {
        return $this->json($registration->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $registration = new Registration();
        $this->fill($registration, $request->toArray());

        return $this->save($registration, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(Registration $registration, Request $request): JsonResponse
    {
        $this->fill($registration, $request->toArray());

        return $this->save($registration);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Registration $registration): JsonResponse
    {
        return $this->remove($registration);
    }

    private function fill(Registration $registration, array $data): void
    {
        if (array_key_exists('event_id', $data)) {
            $registration->setEvent($this->reference(Event::class, $data['event_id'], 'event_id'));
        }
        if (array_key_exists('participant_id', $data)) {
            $registration->setParticipant($this->reference(Participant::class, $data['participant_id'], 'participant_id'));
        }
        if (array_key_exists('status', $data)) {
            $registration->setStatus($data['status']);
        }
    }
}
