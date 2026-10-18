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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/registrations', name: 'api_registrations_')]
class RegistrationController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function index(RegistrationRepository $repository, Request $request): JsonResponse
    {
        return $this->paginate($repository->createQueryBuilder('r')->orderBy('r.id'), $request, [
            'id' => ['id', self::FILTER_INT],
            'event_id' => ['event', self::FILTER_INT],
            'participant_id' => ['participant', self::FILTER_INT],
            'status' => ['status', self::FILTER_EXACT],
            'registered_at' => ['registeredAt', self::FILTER_DATE],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function show(Registration $registration): JsonResponse
    {
        return $this->json($registration->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function create(Request $request): JsonResponse
    {
        $registration = new Registration();
        $this->fill($registration, $request->toArray());

        return $this->save($registration, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_MANAGER')]
    public function update(Registration $registration, Request $request): JsonResponse
    {
        $this->fill($registration, $request->toArray());

        return $this->save($registration);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
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
