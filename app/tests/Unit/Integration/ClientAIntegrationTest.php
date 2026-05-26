<?php

namespace App\Tests\Unit\Integration;

use App\Integration\ClientAIntegration;
use PHPUnit\Framework\TestCase;

class ClientAIntegrationTest extends TestCase
{
    private ClientAIntegration $integration;

    protected function setUp(): void
    {
        $this->integration = new ClientAIntegration();
    }

    public function testGetClientId(): void
    {
        $this->assertSame('client_a', $this->integration->getClientId());
    }

    public function testTransformMapsOrderId(): void
    {
        $payload = [
            'event'        => 'order/created',
            'order_id'     => 3001,
            'amount_cents' => 5000,
            'currency'     => 'EUR',
            'client_id'    => 'client_a',
        ];

        $result = $this->integration->transform($payload);

        $this->assertSame(3001, $result['external_order_id']);
        $this->assertSame('client_a', $result['client_id']);
        $this->assertSame(5000, $result['amount_cents']);
        $this->assertSame('EUR', $result['currency']);
        $this->assertSame('order/created', $result['event_type']);
    }

    public function testTransformHandlesMissingFields(): void
    {
        $result = $this->integration->transform([]);

        $this->assertNull($result['external_order_id']);
        $this->assertSame(0, $result['amount_cents']);
        $this->assertSame('EUR', $result['currency']);
        $this->assertSame([], $result['line_items']);
    }

    public function testTransformIncludesNormalisedAt(): void
    {
        $result = $this->integration->transform(['order_id' => 1]);

        $this->assertArrayHasKey('normalised_at', $result);
        $this->assertNotEmpty($result['normalised_at']);
    }

}