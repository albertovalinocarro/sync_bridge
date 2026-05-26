<?php

namespace App\Tests\Unit\Entity;

use App\Entity\WebhookEvent;
use PHPUnit\Framework\TestCase;

class WebhookEventTest extends TestCase
{
    public function testDefaultStatusIsPending(): void
    {
        $event = new WebhookEvent();

        $this->assertSame('pending', $event->getStatus());
    }

    public function testCreatedAtIsSetAutomatically(): void
    {
        $event = new WebhookEvent();

        $this->assertInstanceOf(\DateTimeImmutable::class, $event->getCreatedAt());
    }

    public function testCreatedAtIsRecentTimestamp(): void
    {
        $before = new \DateTimeImmutable();
        $event  = new WebhookEvent();
        $after  = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $event->getCreatedAt());
        $this->assertLessThanOrEqual($after, $event->getCreatedAt());
    }

    public function testSetAndGetClientId(): void
    {
        $event = new WebhookEvent();
        $event->setClientId('client_a');

        $this->assertSame('client_a', $event->getClientId());
    }

    public function testSetAndGetPayload(): void
    {
        $payload = ['event' => 'order/created', 'order_id' => 1001];
        $event = new WebhookEvent();
        $event->setPayload($payload);

        $this->assertSame($payload, $event->getPayload());
    }

    public function testSetAndGetIdempotencyKey():void
    {
        $event = new WebhookEvent();
        $event->setIdempotencyKey('abc123');

        $this->assertSame('abc123', $event->getIdempotencyKey());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $event = new WebhookEvent();

        $this->assertNull($event->getId());
    }

}