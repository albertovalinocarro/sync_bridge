<?php

namespace App\Integration;

interface ClientIntegrationInterface
{
    public function getClientId(): string;

    public function transform(array $payload): array;
}