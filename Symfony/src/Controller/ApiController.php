<?php

namespace App\Controller;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ApiController extends AbstractController
{
    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly ValidatorInterface $validator,
    ) {
    }

    protected function save(object $entity, int $status = Response::HTTP_OK): JsonResponse
    {
        $errors = [];
        foreach ($this->validator->validate($entity) as $violation) {
            $errors[$violation->getPropertyPath()][] = $violation->getMessage();
        }

        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $this->em->persist($entity);
        $this->em->flush();

        return $this->json($entity->toArray(), $status);
    }

    protected function remove(object $entity): JsonResponse
    {
        try {
            $this->em->remove($entity);
            $this->em->flush();
        } catch (ForeignKeyConstraintViolationException) {
            return $this->json(
                ['error' => 'Record cannot be deleted because it is referenced by other records.'],
                Response::HTTP_CONFLICT,
            );
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    protected function list(array $entities): JsonResponse
    {
        return $this->json(array_map(static fn (object $entity) => $entity->toArray(), $entities));
    }

    protected function reference(string $class, mixed $id, string $field): object
    {
        $entity = $id === null ? null : $this->em->find($class, $id);

        if (!$entity) {
            throw new UnprocessableEntityHttpException(sprintf('Field "%s": record with id "%s" not found.', $field, $id));
        }

        return $entity;
    }

    protected function date(?string $value, string $field): ?\DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception) {
            throw new UnprocessableEntityHttpException(sprintf('Field "%s": invalid date "%s".', $field, $value));
        }
    }
}
