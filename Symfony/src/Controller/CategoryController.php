<?php

namespace App\Controller;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(CategoryRepository $repository, Request $request): JsonResponse
    {
        return $this->paginate($repository->createQueryBuilder('c')->orderBy('c.id'), $request, [
            'id' => ['id', self::FILTER_INT],
            'name' => ['name', self::FILTER_LIKE],
            'description' => ['description', self::FILTER_LIKE],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Category $category): JsonResponse
    {
        return $this->json($category->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $category = new Category();
        $this->fill($category, $request->toArray());

        return $this->save($category, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(Category $category, Request $request): JsonResponse
    {
        $this->fill($category, $request->toArray());

        return $this->save($category);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Category $category): JsonResponse
    {
        return $this->remove($category);
    }

    private function fill(Category $category, array $data): void
    {
        if (array_key_exists('name', $data)) {
            $category->setName($data['name']);
        }
        if (array_key_exists('description', $data)) {
            $category->setDescription($data['description']);
        }
    }
}
