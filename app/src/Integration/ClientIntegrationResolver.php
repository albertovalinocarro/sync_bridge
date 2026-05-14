<?php

namespace App\Integration;

use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final class ClientIntegrationResolver
{
    /**
     * @var array<string, ClientIntegrationInterface>
     */
    private array $integrations = [];

    public function __construct(
        #[TaggedIterator('app.client_integration')]
        iterable $integrations
    ) {
        foreach ($integrations as $integration) {
            $this->integrations[$integration->getClientId()] = $integration;
        }
    }

    public function resolve(string $clientId): ClientIntegrationInterface
    {
        if (!isset($this->integrations[$clientId])) {
            throw new \RuntimeException(
                sprintf('No integration found for client "%s"', $clientId)
            );
        }

        return $this->integrations[$clientId];
    }

    public function supports(string $clientId): bool
    {
        return isset($this->integrations[$clientId]);
    }
}