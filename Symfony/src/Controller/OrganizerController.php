<?php

namespace App\Controller;

use App\Entity\Organizer;
use App\Repository\OrganizerRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/organizers', name: 'api_organizers_')]
class OrganizerController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function index(OrganizerRepository $repository, Request $request): JsonResponse
    {
        return $this->paginate($repository->createQueryBuilder('o')->orderBy('o.id'), $request, [
            'id' => ['id', self::FILTER_INT],
            'name' => ['name', self::FILTER_LIKE],
            'email' => ['email', self::FILTER_LIKE],
            'phone' => ['phone', self::FILTER_LIKE],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function show(Organizer $organizer): JsonResponse
    {
        return $this->json($organizer->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function create(Request $request): JsonResponse
    {
        $organizer = new Organizer();
        $this->fill($organizer, $request->toArray());

        return $this->save($organizer, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    #[IsGranted('ROLE_MANAGER')]
    public function update(Organizer $organizer, Request $request): JsonResponse
    {
        $this->fill($organizer, $request->toArray());

        return $this->save($organizer);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    #[IsGranted('ROLE_ADMIN')]
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
