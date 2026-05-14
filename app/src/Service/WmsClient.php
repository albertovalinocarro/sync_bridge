<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WmsClient
{
    private const WMS_ENDPOINT = 'http://nginx/mock/wms/orders';

    public function __construct(
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger
    ) {}

    public function syncOrder(array $normalisedPayload): array
    {
        $this->logger->info('Sending order to WMS', [
            'external_order_id' => $normalisedPayload['external_order_id'] ?? null,
            'client_id'         => $normalisedPayload['client_id'] ?? null,
        ]);

        $response = $this->httpClient->request('POST', self::WMS_ENDPOINT, [
            'json'    => $normalisedPayload,
            'timeout' => 10,
        ]);

        $data = $response->toArray();

        $this->logger->info('WMS response received', [
            'wms_status'   => $data['wms_status'] ?? null,
            'wms_order_id' => $data['wms_order_id'] ?? null,
        ]);

        return $data;
    }
}