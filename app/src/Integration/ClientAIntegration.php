<?php

namespace App\Integration;

final class ClientAIntegration implements ClientIntegrationInterface
{
    public function getClientId(): string
    {
        return 'client_a';
    }

    public function transform(array $payload): array
    {
        // Client A uses "order_id" and sends amounts in cents
        return [
            'external_order_id' => $payload['order_id'] ?? null,
            'client_id' => $this->getClientId(),
            'event_type' => $payload['event'] ?? null,
            'amount_cents' => $payload['amount_cents'] ?? 0,
            'currency' => $payload['currency'] ?? 'EUR',
            'line_items' => $payload['line_items'] ?? [],
            'normalised_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }
}