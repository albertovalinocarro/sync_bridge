<?php

namespace App\Controller;

use App\Entity\WebhookEvent;
use App\Message\OrderReceivedMessage;
use App\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController extends AbstractController
{

    public function __construct(
        private EntityManagerInterface $entityManager,
        private MessageBusInterface $bus,
        private WebhookEventRepository $repository
    ) {}

    // Define a route for handling incoming webhook requests from the e-commerce platform
    #[Route('/webhook/order', name: 'webhook_order', methods: ['POST'])]
    // This method will be called when the e-commerce platform sends an order-related webhook
    public function order(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        // Generate idempotency key from the natural unique identifiers
        $idempotencyKey = $this->generateIdempotencyKey($payload);

        // Check for duplicate - id already seen, return early
        $existing = $this->repository->findByIdempotencyKey($idempotencyKey);

        if($existing !== null) {
            return $this->json([
                'status'     => 'duplicate',
                'message'    => 'Webhook already received',
                'id'         => $existing->getId(),
                'clientId'   => $existing->getClientId(),
            ], 200);
        }

        try {
            $event = new WebhookEvent();
            $event->setClientId($payload['client_id'] ?? 'unknown');
            $event->setEventType($payload['event'] ?? 'unknown');
            $event->setPayload($payload);
            $event->setIdempotencyKey($idempotencyKey);

            $this->entityManager->persist($event);
            $this->entityManager->flush();    
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            // Race condition — another request persisted the same key simultaneously
            $existing = $this->repository->findByIdempotencyKey($idempotencyKey);

            return $this->json([
                'status'   => 'duplicate',
                'message'  => 'Webhook already received',
                'id'       => $existing?->getId(),
                'clientId' => $existing?->getClientId(),
            ], 200);
        }

        $this->bus->dispatch(new OrderReceivedMessage($event->getId()));

        return $this->json([
            'status' => 'queued',
            'event' => $event->getEventType(),
            'id' => $event->getId(),
            'clientId' => $event->getClientId(),
        ], 202);
    }
    /*
    Invoke-WebRequest -Uri "http://localhost:8080/webhook/order" `
        -Method POST `
        -ContentType "application/json" `
        -Body '{"event":"order/created","order_id":1001}' `
        -UseBasicParsing
    */
    private function generateIdempotencyKey(array $payload): string
    {
        $clientId = $payload['client_id'] ?? 'unknown';
        $eventType = $payload['event'] ?? 'unknown';

        // User order_id for Client A, reference for Client B
        // Falls back to a hash of the full payload if neither present
        $uniqueId = $payload['order_id']
            ?? $payload['reference']
            ?? md5(json_encode($payload));

        return hash('sha256', implode('|', [$clientId, $eventType, $uniqueId]));
    }
}
