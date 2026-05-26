<?php

namespace App\Tests\Functional\Controller;

use App\Entity\WebhookEvent;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Envelope;

class WebhookControllerTest extends WebTestCase
{
    private $client;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $this->entityManager = static::getContainer()
            ->get(EntityManagerInterface::class);

        // Clean up webhook_event table before each test
        $this->entityManager->createQuery('DELETE FROM App\Entity\WebhookEvent')->execute();
    }

    private function postWebhook(array $payload): void
    {
        $this->client->request(
            'POST',
            '/webhook/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode($payload)
        );
    }

    public function testValidWebhookReturns202(): void
    {
        $this->postWebhook([
            'event'        => 'order/created',
            'client_id'    => 'client_a',
            'order_id'     => 1001,
            'amount_cents' => 5000,
            'currency'     => 'EUR',
        ]);

        $this->assertResponseStatusCodeSame(202);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('queued', $data['status']);
        $this->assertSame('order/created', $data['event']);
        $this->assertArrayHasKey('id', $data);
    }

    public function testValidWebhookPersistsToDatabase(): void
    {
        $this->postWebhook([
            'event'        => 'order/created',
            'client_id'    => 'client_a',
            'order_id'     => 2001,
            'amount_cents' => 9900,
            'currency'     => 'EUR',
        ]);

        $this->assertResponseStatusCodeSame(202);

        $event = $this->entityManager
            ->getRepository(WebhookEvent::class)
            ->findOneBy(['clientId' => 'client_a']);

        $this->assertNotNull($event);
        $this->assertSame('pending', $event->getStatus());
        $this->assertSame('order/created', $event->getEventType());
        $this->assertNotNull($event->getIdempotencyKey());
    }

    public function testInvalidPayloadReturns400(): void
    {
        $this->client->request(
            'POST',
            '/webhook/order',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            'not valid json {'
        );

        $this->assertResponseStatusCodeSame(400);

        $data = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('Invalid JSON payload', $data['error']);
    }

    public function testGetRequestReturns405(): void
    {
        $this->client->request('GET', '/webhook/order');

        $this->assertResponseStatusCodeSame(405);
    }

    public function testDuplicateWebhookReturns200(): void
    {
        $payload = [
            'event'        => 'order/created',
            'client_id'    => 'client_a',
            'order_id'     => 3001,
            'amount_cents' => 5000,
            'currency'     => 'EUR',
        ];

        // First request
        $this->postWebhook($payload);
        $this->assertResponseStatusCodeSame(202);

        $firstData = json_decode($this->client->getResponse()->getContent(), true);

        // Second request — identical payload
        $this->postWebhook($payload);
        $this->assertResponseStatusCodeSame(200);

        $secondData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertSame('duplicate', $secondData['status']);
        $this->assertSame($firstData['id'], $secondData['id']);
    }

    public function testDuplicateWebhookDoesNotCreateNewDatabaseRow(): void
    {
        $payload = [
            'event'        => 'order/created',
            'client_id'    => 'client_a',
            'order_id'     => 4001,
            'amount_cents' => 5000,
            'currency'     => 'EUR',
        ];

        $this->postWebhook($payload);
        $this->postWebhook($payload);

        $count = $this->entityManager
            ->getRepository(WebhookEvent::class)
            ->count([]);

        $this->assertSame(1, $count);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }
}