<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\OffsetPaginator;
use Doctrine\ORM\Tools\Pagination\Window;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

abstract class ApiController extends AbstractController
{
    protected const FILTER_INT = 'int';
    protected const FILTER_EXACT = 'exact';
    protected const FILTER_LIKE = 'like';
    protected const FILTER_NUMBER = 'number';
    protected const FILTER_DATE = 'date';

    private const DEFAULT_ITEMS_PER_PAGE = 10;
    private const MAX_ITEMS_PER_PAGE = 100;

    public function __construct(
        protected readonly EntityManagerInterface $em,
        protected readonly ValidatorInterface $validator,
        protected readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    protected function applyPassword(User $user, mixed $password): ?JsonResponse
    {
        if (!is_string($password) || mb_strlen($password) < 8 || mb_strlen($password) > 4096) {
            return $this->json(
                ['errors' => ['password' => ['Password must be a string of at least 8 characters.']]],
                Response::HTTP_UNPROCESSABLE_ENTITY,
            );
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, $password));

        return null;
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

    protected function paginate(QueryBuilder $qb, Request $request, array $filters): JsonResponse
    {
        $errors = [];

        $page = $this->intQuery($request, 'page', 1, PHP_INT_MAX, $errors) ?? 1;
        $itemsPerPage = $this->intQuery($request, 'itemsPerPage', 1, self::MAX_ITEMS_PER_PAGE, $errors) ?? self::DEFAULT_ITEMS_PER_PAGE;

        $this->applyFilters($qb, $request, $filters, $errors);

        if ($errors) {
            return $this->json(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $result = (new OffsetPaginator())->paginate($qb, Window::fromPageNumberAndSize($page, $itemsPerPage));

        return $this->json([
            'data' => array_map(static fn (object $entity) => $entity->toArray(), $result->getItems()),
            'meta' => [
                'page' => $result->getPageNumber(),
                'itemsPerPage' => $itemsPerPage,
                'totalItems' => $result->getTotalCount(),
                'totalPages' => $result->getPageCount(),
            ],
        ]);
    }

    private function applyFilters(QueryBuilder $qb, Request $request, array $filters, array &$errors): void
    {
        $alias = $qb->getRootAliases()[0];

        foreach ($filters as $param => [$field, $type]) {
            $column = $alias . '.' . $field;

            switch ($type) {
                case self::FILTER_INT:
                    $value = $this->intQuery($request, $param, 1, PHP_INT_MAX, $errors);
                    $this->where($qb, $column, '=', $value, $param);
                    break;

                case self::FILTER_EXACT:
                    $this->where($qb, $column, '=', $this->query($request, $param), $param);
                    break;

                case self::FILTER_LIKE:
                    $value = $this->query($request, $param);
                    $this->where($qb, $column, 'LIKE', $value === null ? null : '%' . addcslashes($value, '%_\\') . '%', $param);
                    break;

                case self::FILTER_NUMBER:
                    foreach ([$param => '=', $param . '_min' => '>=', $param . '_max' => '<='] as $name => $operator) {
                        $value = $this->query($request, $name);
                        if ($value !== null && !is_numeric($value)) {
                            $errors[$name][] = 'This value should be a number.';
                            continue;
                        }
                        $this->where($qb, $column, $operator, $value, $name);
                    }
                    break;

                case self::FILTER_DATE:
                    $day = $this->dateQuery($request, $param, $errors);
                    if ($day) {
                        $this->where($qb, $column, '>=', $day['start'], $param . '_day_start');
                        $this->where($qb, $column, '<', $day['start']->modify('+1 day'), $param . '_day_end');
                    }

                    $from = $this->dateQuery($request, $param . '_from', $errors);
                    if ($from) {
                        $this->where($qb, $column, '>=', $from['value'], $param . '_from');
                    }

                    $to = $this->dateQuery($request, $param . '_to', $errors);
                    if ($to) {
                        $to['dateOnly']
                            ? $this->where($qb, $column, '<', $to['start']->modify('+1 day'), $param . '_to')
                            : $this->where($qb, $column, '<=', $to['value'], $param . '_to');
                    }
                    break;
            }
        }
    }

    private function where(QueryBuilder $qb, string $column, string $operator, mixed $value, string $param): void
    {
        if ($value === null) {
            return;
        }

        $qb->andWhere(sprintf('%s %s :%s', $column, $operator, $param))->setParameter($param, $value);
    }

    private function query(Request $request, string $name): ?string
    {
        $value = $request->query->get($name);

        return $value === null || $value === '' ? null : (string) $value;
    }

    private function intQuery(Request $request, string $name, int $min, int $max, array &$errors): ?int
    {
        $value = $this->query($request, $name);

        if ($value === null) {
            return null;
        }

        if (!ctype_digit($value) || (int) $value < $min || (int) $value > $max) {
            $errors[$name][] = sprintf('This value should be an integer between %d and %d.', $min, $max);

            return null;
        }

        return (int) $value;
    }

    private function dateQuery(Request $request, string $name, array &$errors): ?array
    {
        $value = $this->query($request, $name);

        if ($value === null) {
            return null;
        }

        try {
            $date = new \DateTimeImmutable($value);
        } catch (\Exception) {
            $errors[$name][] = 'This value is not a valid date.';

            return null;
        }

        return [
            'value' => $date,
            'start' => $date->setTime(0, 0),
            'dateOnly' => (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value),
        ];
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
