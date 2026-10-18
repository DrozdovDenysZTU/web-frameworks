<?php

namespace App\Controller;

use App\Entity\Venue;
use App\Repository\VenueRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/venues', name: 'api_venues_')]
class VenueController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(VenueRepository $repository): JsonResponse
    {
        return $this->list($repository->findAll());
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Venue $venue): JsonResponse
    {
        return $this->json($venue->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $venue = new Venue();
        $this->fill($venue, $request->toArray());

        return $this->save($venue, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(Venue $venue, Request $request): JsonResponse
    {
        $this->fill($venue, $request->toArray());

        return $this->save($venue);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Venue $venue): JsonResponse
    {
        return $this->remove($venue);
    }

    private function fill(Venue $venue, array $data): void
    {
        if (array_key_exists('name', $data)) {
            $venue->setName($data['name']);
        }
        if (array_key_exists('address', $data)) {
            $venue->setAddress($data['address']);
        }
        if (array_key_exists('city', $data)) {
            $venue->setCity($data['city']);
        }
        if (array_key_exists('capacity', $data)) {
            $venue->setCapacity($data['capacity'] === null ? null : (int) $data['capacity']);
        }
    }
}
