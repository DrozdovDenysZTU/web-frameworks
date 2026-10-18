<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\Organizer;
use App\Entity\Venue;
use App\Repository\EventRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/events', name: 'api_events_')]
class EventController extends ApiController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EventRepository $repository, Request $request): JsonResponse
    {
        return $this->paginate($repository->createQueryBuilder('e')->orderBy('e.startAt')->addOrderBy('e.id'), $request, [
            'id' => ['id', self::FILTER_INT],
            'title' => ['title', self::FILTER_LIKE],
            'description' => ['description', self::FILTER_LIKE],
            'start_at' => ['startAt', self::FILTER_DATE],
            'end_at' => ['endAt', self::FILTER_DATE],
            'price' => ['price', self::FILTER_NUMBER],
            'category_id' => ['category', self::FILTER_INT],
            'venue_id' => ['venue', self::FILTER_INT],
            'organizer_id' => ['organizer', self::FILTER_INT],
        ]);
    }

    #[Route('/{id}', name: 'show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Event $event): JsonResponse
    {
        return $this->json($event->toArray());
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $event = new Event();
        $this->fill($event, $request->toArray());

        return $this->save($event, Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'update', requirements: ['id' => '\d+'], methods: ['PUT', 'PATCH'])]
    public function update(Event $event, Request $request): JsonResponse
    {
        $this->fill($event, $request->toArray());

        return $this->save($event);
    }

    #[Route('/{id}', name: 'delete', requirements: ['id' => '\d+'], methods: ['DELETE'])]
    public function delete(Event $event): JsonResponse
    {
        return $this->remove($event);
    }

    private function fill(Event $event, array $data): void
    {
        if (array_key_exists('title', $data)) {
            $event->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $event->setDescription($data['description']);
        }
        if (array_key_exists('start_at', $data)) {
            $event->setStartAt($this->date($data['start_at'], 'start_at'));
        }
        if (array_key_exists('end_at', $data)) {
            $event->setEndAt($this->date($data['end_at'], 'end_at'));
        }
        if (array_key_exists('price', $data)) {
            $event->setPrice($data['price'] === null ? null : (string) $data['price']);
        }
        if (array_key_exists('category_id', $data)) {
            $event->setCategory($this->reference(Category::class, $data['category_id'], 'category_id'));
        }
        if (array_key_exists('venue_id', $data)) {
            $event->setVenue($this->reference(Venue::class, $data['venue_id'], 'venue_id'));
        }
        if (array_key_exists('organizer_id', $data)) {
            $event->setOrganizer($this->reference(Organizer::class, $data['organizer_id'], 'organizer_id'));
        }
    }
}
