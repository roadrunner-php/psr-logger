<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit;

use Spiral\Goridge\RPC\CodecInterface;
use Spiral\Goridge\RPC\RPCInterface;

/**
 * RPC spy for capturing and inspecting RPC calls in unit tests.
 * 
 * This test double implements RPCInterface to allow testing of components
 * that depend on RPC communication without making actual RPC calls.
 * It records all method calls for later inspection and assertion.
 */
class RpcSpy implements RPCInterface
{
    public array $calls = [];

    public function call(string $method, mixed $payload, mixed $options = null): mixed
    {
        $this->calls[] = ['method' => $method, 'payload' => $payload, 'options' => $options];
        return null;
    }

    public function withCodec(CodecInterface $codec): RPCInterface
    {
        return $this;
    }

    public function withServicePrefix(string $service): RPCInterface
    {
        return $this;
    }

    public function getLastCall(): ?array
    {
        return end($this->calls) ?: null;
    }

    public function getCallCount(): int
    {
        return count($this->calls);
    }

    public function reset(): void
    {
        $this->calls = [];
    }
}
