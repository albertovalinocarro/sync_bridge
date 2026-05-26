<?php

namespace App\Tests\Unit\Integration;

use App\Integration\ClientBIntegration;
use PHPUnit\Framework\TestCase;

class ClientBIntegrationTest extends TestCase
{
    private ClientBIntegration $integration;

    protected function setUp(): void
    {
        $this->integration = new ClientBIntegration();
    }

    public function testGetClientId(): void
    {
        $this->assertSame('client_b', $this->integration->getClientId());
    }

    public function testTransformMapsReference(): void
    {
        $payload = [
            'event'     => 'order/created',
            'reference' => 'ORD-9999',
            'amount'    => 49.99,
            'currency'  => 'GBP',
            'client_id' => 'client_b',
        ];

        $result = $this->integration->transform($payload);

        $this->assertSame('ORD-9999', $result['external_order_id']);
        $this->assertSame('client_b', $result['client_id']);
        $this->assertSame('GBP', $result['currency']);
        $this->assertSame('order/created', $result['event_type']);
    }

    public function testTransformConvertsAmountToCents(): void
    {
        $result = $this->integration->transform([
            'amount'   => 49.99,
            'currency' => 'GBP',
        ]);

        $this->assertSame(4999, $result['amount_cents']);

    }

    public function testTransformHandlesZeroAmount(): void
    {
        $result = $this->integration->transform([]);

        $this->assertSame(0, $result['amount_cents']);
    }
}