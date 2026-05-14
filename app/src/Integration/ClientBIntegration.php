<?php

namespace App\Integration;

final class ClientBIntegration implements ClientIntegrationInterface
{
    public function getClientId(): string
    {
        return 'client_b';
    }

    public function transform(array $payload): array
    {
        // Client B uses "reference" instead of "order_id"
        // and sends amounts in full currency units, not cents
        return [
            'external_order_id' => $payload['reference'] ?? null,
            'client_id' => $this->getClientId(),
            'event_type' => $payload['event'] ?? null,
            'amount_cents' => isset($payload['amount']) ? (int) round($payload['amount'] * 100) : 0,
            'currency' => $payload['currency'] ?? 'EUR',
            'line_items' => $payload['line_items'] ?? [],
            'normalised_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];
    }

}