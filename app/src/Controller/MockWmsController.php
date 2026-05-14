<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class MockWmsController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    #[Route('/mock/wms/orders', name: 'mock_wms_orders', methods: ['POST'])]
    public function receiveOrder(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        $this->logger->info('Mock WMS received order', [
            'external_order_id' => $payload['external_order_id'] ?? null,
            'client_id' => $payload['client_id'] ?? null,
            'amount_cents' => $payload['amount_cents'] ?? null,
        ]);

        return $this->json([
            'wms_status' => 'accepted',
            'wms_order_id' => 'WMS-' . strtoupper(uniqid()),
        ], 201);
    }
}