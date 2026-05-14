<?php

namespace App\MessageHandler;

use App\Integration\ClientIntegrationResolver;
use App\Message\OrderReceivedMessage;
use App\Repository\WebhookEventRepository;
use App\Service\WmsClient;
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
        private ClientIntegrationResolver $resolver,
        private WmsClient $wmsClient
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

        try{
            $integration = $this->resolver->resolve($clientId);
            $normalised = $integration->transform($event->getPayload());
            $wmsResponse = $this->wmsClient->syncOrder($normalised);

            // This is where real processing will go — syncing to WMS/ERP
            $this->logger->info('Order synced to WMS', [
                'id' => $event->getId(),
                'client_id' => $clientId,
                'wms_order_id' => $wmsResponse['wms_order_id'] ?? null,
            ]);

            $event->setStatus('synced');
        } catch (\Throwable $e) {
            $this->logger->error('Failed to sync order to WMS', [
                'id' => $event->getId(),
                'error' => $e->getMessage(),
            ]);

            $event->setStatus('failed');

        } finally {
            $this->entityManager->flush();
        }

    }
}