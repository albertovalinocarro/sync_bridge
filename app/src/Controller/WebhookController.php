<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

final class WebhookController extends AbstractController
{
    // Define a route for handling incoming webhook requests from the e-commerce platform
    #[Route('/webhook/order', name: 'webhook_order', methods: ['POST'])]
    // This method will be called when the e-commerce platform sends an order-related webhook
    public function order(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if (!$payload) {
            return $this->json(['error' => 'Invalid JSON payload'], 400);
        }

        return $this->json([
            'status' => 'received',
            'event' => $payload['event'] ?? 'unknown',
        ], 202);
    }
    /*
    Invoke-WebRequest -Uri "http://localhost:8080/webhook/order" `
        -Method POST `
        -ContentType "application/json" `
        -Body '{"event":"order/created","order_id":1001}' `
        -UseBasicParsing
    */
}
