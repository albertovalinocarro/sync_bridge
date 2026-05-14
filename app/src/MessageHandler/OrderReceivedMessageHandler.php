<?php

namespace App\MessageHandler;

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
        private LoggerInterface $logger
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

        // This is where real processing will go — syncing to WMS/ERP
        // For now we just update the status to show async processing worked
        $this->logger->info('Processing webhook event', [
            'id'        => $event->getId(),
            'client_id' => $event->getClientId(),
            'event'     => $event->getEventType(),
        ]);

        $event->setStatus('processed');
        $this->entityManager->flush();
    }
}