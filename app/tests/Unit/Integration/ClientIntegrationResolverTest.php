<?php

namespace App\Tests\Unit\Integration;

use App\Integration\ClientAIntegration;
use App\Integration\ClientBIntegration;
use App\Integration\ClientIntegrationResolver;
use PHPUnit\Framework\TestCase;

class ClientIntegrationResolverTest extends TestCase
{
    private ClientIntegrationResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ClientIntegrationResolver([
            new ClientAIntegration(),
            new ClientBIntegration(),
        ]);
    }

    public function testResolvesClientA(): void
    {
        $integration = $this->resolver->resolve('client_a');
    
        $this->assertInstanceOf(ClientAIntegration::class, $integration);
    }

    public function testResolvesClientB(): void
    {
        $integration = $this->resolver->resolve('client_b');

        $this->assertInstanceOf(ClientBIntegration::class, $integration);
    }

    public function testSupportsKnownClient(): void
    {
        $this->assertTrue($this->resolver->supports('client_a'));
        $this->assertTrue($this->resolver->supports('client_b'));
    }

    public function testDoesNotSupportUnknownClient(): void
    {
        $this->assertFalse($this->resolver->supports('client_unknown'));
    }

    public function testThrowsExceptionForUnknownClient(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No integration found for client "client_unknown"');

        $this->resolver->resolve('client_unknown');
    }

}