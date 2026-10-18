<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/users', name: 'api_users_')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(UserRepository $repository, Request $request): JsonResponse
    {
        return $this->paginate($repository->createQueryBuilder('u')->orderBy('u.id'), $request, [
            'id' => ['id', self::FILTER_INT],
            'name' => ['name', self::FILTER_LIKE],
            'email' => ['email', self::FILTER_LIKE],
            'role' => ['role', self::FILTER_EXACT],
            'created_at' => ['createdAt', self::FILTER_DATE],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(User $user): JsonResponse
    {
        return $this->json($user->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $user = new User();
        $this->fill($user, $data);

        if ($error = $this->applyPassword($user, $data['password'] ?? null)) {
            return $error;
        }

        return $this->save($user, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(User $user, Request $request): JsonResponse
    {
        $data = $request->toArray();
        $this->fill($user, $data);

        if (array_key_exists('password', $data) && ($error = $this->applyPassword($user, $data['password']))) {
            return $error;
        }

        return $this->save($user);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(User $user, #[CurrentUser] User $currentUser): JsonResponse
    {
        if ($user->getId() === $currentUser->getId()) {
            return $this->json(['error' => 'You cannot delete your own account.'], Response::HTTP_CONFLICT);
        }

        return $this->remove($user);
    }

    private function fill(User $user, array $data): void
    {
        if (array_key_exists('name', $data)) {
            $user->setName($data['name']);
        }
        if (array_key_exists('email', $data)) {
            $user->setEmail($data['email']);
        }
        if (array_key_exists('role', $data)) {
            $user->setRole($data['role']);
        }
    }
}
