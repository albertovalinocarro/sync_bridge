<?php

namespace App\Message;

final class OrderReceivedMessage
{
    public function __construct(
        public readonly int $webhookEventId
    ) {}
}