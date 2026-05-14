<?php

namespace App\Controller;

use App\Repository\WebhookEventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class SyncEventController extends AbstractController
{
    public function __construct(
        private WebhookEventRepository $repository
    ) {}

    #[Route('/sync-events', name: 'sync_events_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $clientId = $request->query->get('client_id');
        $status = $request->query->get('status');
        $limit = min((int) $request->query->get('limit', 20), 100);
        $offset = (int) $request->query->get('offset', 0);

        $events = $this->repository->findByFilters($clientId, $status, $limit, $offset);
        $total = $this->repository->countByFilters($clientId, $status);

        return $this->json([
            'data' => array_map([$this, 'serialiseEvent'], $events),
            'pagination' => [
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total,
            ],
        ]);

    }

    #[Route('/sync-events/{id}', name: 'sync_events_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $event = $this->repository->find($id);

        if (!$event) {
            return $this->json(['error' => 'Event not found'], 404);
        }

        return $this->json($this->serialiseEvent($event));
    }

    private function serialiseEvent(object $event): array
    {
        return [
            'id' => $event->getId(),
            'client_id' => $event->getClientId(),
            'event_type' => $event->getEventType(),
            'status' => $event->getStatus(),
            'payload' => $event->getPayload(),
            'created_at' => $event->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}