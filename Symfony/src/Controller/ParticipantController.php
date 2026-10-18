<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/participants', name: 'api_participants_')]
class ParticipantController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(ParticipantRepository $repository): JsonResponse
    {
        return $this->list($repository->findAll());
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Participant $participant): JsonResponse
    {
        return $this->json($participant->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $participant = new Participant();
        $this->fill($participant, $request->toArray());

        return $this->save($participant, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(Participant $participant, Request $request): JsonResponse
    {
        $this->fill($participant, $request->toArray());

        return $this->save($participant);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Participant $participant): JsonResponse
    {
        return $this->remove($participant);
    }

    private function fill(Participant $participant, array $data): void
    {
        if (array_key_exists('first_name', $data)) {
            $participant->setFirstName($data['first_name']);
        }
        if (array_key_exists('last_name', $data)) {
            $participant->setLastName($data['last_name']);
        }
        if (array_key_exists('email', $data)) {
            $participant->setEmail($data['email']);
        }
        if (array_key_exists('phone', $data)) {
            $participant->setPhone($data['phone']);
        }
    }
}
