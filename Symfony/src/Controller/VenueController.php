<?php

namespace App\Controller;

use App\Entity\Venue;
use App\Repository\VenueRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/venues', name: 'api_venues_')]
class VenueController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function index(VenueRepository $repository, Request $request): JsonResponse
    {
        return $this->paginate($repository->createQueryBuilder('v')->orderBy('v.id'), $request, [
            'id' => ['id', self::FILTER_INT],
            'name' => ['name', self::FILTER_LIKE],
            'address' => ['address', self::FILTER_LIKE],
            'city' => ['city', self::FILTER_LIKE],
            'capacity' => ['capacity', self::FILTER_NUMBER],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function show(Venue $venue): JsonResponse
    {
        return $this->json($venue->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function create(Request $request): JsonResponse
    {
        $venue = new Venue();
        $this->fill($venue, $request->toArray());

        return $this->save($venue, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_MANAGER')]
    public function update(Venue $venue, Request $request): JsonResponse
    {
        $this->fill($venue, $request->toArray());

        return $this->save($venue);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
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
