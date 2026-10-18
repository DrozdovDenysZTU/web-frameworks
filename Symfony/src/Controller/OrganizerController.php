<?php

namespace App\Controller;

use App\Entity\Organizer;
use App\Repository\OrganizerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/organizers', name: 'api_organizers_')]
class OrganizerController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(OrganizerRepository $repository): JsonResponse
    {
        return $this->list($repository->findAll());
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Organizer $organizer): JsonResponse
    {
        return $this->json($organizer->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $organizer = new Organizer();
        $this->fill($organizer, $request->toArray());

        return $this->save($organizer, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(Organizer $organizer, Request $request): JsonResponse
    {
        $this->fill($organizer, $request->toArray());

        return $this->save($organizer);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Organizer $organizer): JsonResponse
    {
        return $this->remove($organizer);
    }

    private function fill(Organizer $organizer, array $data): void
    {
        if (array_key_exists('name', $data)) {
            $organizer->setName($data['name']);
        }
        if (array_key_exists('email', $data)) {
            $organizer->setEmail($data['email']);
        }
        if (array_key_exists('phone', $data)) {
            $organizer->setPhone($data['phone']);
        }
    }
}
