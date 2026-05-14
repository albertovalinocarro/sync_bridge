<?php

namespace App\MessageHandler;

use App\Integration\ClientIntegrationResolver;
use App\Message\OrderReceivedMessage;
use App\Repository\WebhookEventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class OrderReceivedMessageHandler
{
    public function __construct(
        private WebhookEventRepository $repository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private ClientIntegrationResolver $resolver
    ) {}

    public function __invoke(OrderReceivedMessage $message): void
    {
        $event = $this->repository->find($message->webhookEventId);

        if (!$event) {
            $this->logger->error('WebhookEvent not found', [
                'id' => $message->webhookEventId
            ]);
            return;
        }

        $clientId = $event->getClientId();

        if (!$this->resolver->supports($clientId)){
            $this->logger->warning('No integration found for client', [
                'client_id' => $clientId
            ]);
            $event->setStatus('skipped');
            $this->entityManager->flush();
            return;
        }

        $integration = $this->resolver->resolve($clientId);
        $normalised = $integration->transform($event->getPayload());

        // This is where real processing will go — syncing to WMS/ERP
        $this->logger->info('Processing webhook event', [
            'id' => $event->getId(),
            'client_id' => $clientId,
            'normalised' => $normalised,
        ]);

        $event->setStatus('processed');
        $this->entityManager->flush();
    }
}