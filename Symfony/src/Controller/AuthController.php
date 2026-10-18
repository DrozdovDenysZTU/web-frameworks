<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends ApiController
{
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = $request->toArray();

        $user = new User();
        $user->setName($data['name'] ?? null);
        $user->setEmail($data['email'] ?? null);
        $user->setRole(User::ROLE_CLIENT);

        if ($error = $this->applyPassword($user, $data['password'] ?? null)) {
            return $error;
        }

        return $this->save($user, Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    #[IsGranted('ROLE_CLIENT')]
    public function me(#[CurrentUser] User $user): JsonResponse
    {
        return $this->json($user->toArray());
    }
}
