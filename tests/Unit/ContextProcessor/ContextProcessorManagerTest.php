<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Internal\ContextProcessor\ContextProcessorInterface;
use RoadRunner\PsrLogger\Internal\ContextProcessor\ContextProcessorManager;

#[CoversClass(ContextProcessorManager::class)]
class ContextProcessorManagerTest extends TestCase
{
    private ContextProcessorManager $manager;

    public function testProcessEmptyContext(): void
    {
        $context = [];
        $result = $this->manager->processContext($context);

        $this->assertSame([], $result);
    }

    public function testProcessScalarValues(): void
    {
        $context = [
            'string' => 'test',
            'int' => 42,
            'float' => 3.14,
            'bool' => true,
            'null' => null,
        ];

        $result = $this->manager->processContext($context);

        $this->assertSame($context, $result);
    }

    public function testProcessDateTime(): void
    {
        $dateTime = new \DateTime('2023-01-01T12:00:00+00:00');
        $context = ['timestamp' => $dateTime];

        $result = $this->manager->processContext($context);

        $this->assertSame(['timestamp' => '2023-01-01T12:00:00+00:00'], $result);
    }

    public function testProcessException(): void
    {
        $exception = new \RuntimeException('Test error', 500);
        $context = ['error' => $exception];

        $result = $this->manager->processContext($context);

        $this->assertIsArray($result['error']);
        $this->assertSame('RuntimeException', $result['error']['class']);
        $this->assertSame('Test error', $result['error']['message']);
        $this->assertSame(500, $result['error']['code']);
        $this->assertArrayHasKey('file', $result['error']);
        $this->assertArrayHasKey('line', $result['error']);
        $this->assertArrayHasKey('trace', $result['error']);
    }

    public function testProcessStringable(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable value';
            }
        };

        $context = ['obj' => $stringable];
        $result = $this->manager->processContext($context);

        $this->assertSame(['obj' => 'stringable value'], $result);
    }

    public function testProcessNestedArray(): void
    {
        $context = [
            'level1' => [
                'level2' => [
                    'value' => 'deep',
                    'number' => 123,
                ],
            ],
        ];

        $result = $this->manager->processContext($context);

        $this->assertSame($context, $result);
    }

    public function testProcessResource(): void
    {
        $resource = \fopen('php://memory', 'r');
        $context = ['handle' => $resource];

        $result = $this->manager->processContext($context);

        \fclose($resource);

        $this->assertSame(['handle' => 'stream resource'], $result);
    }

    public function testProcessObject(): void
    {
        $object = new class {
            public string $publicProp = 'public value';
            private string $privateProp = 'private value';
        };

        $context = ['data' => $object];
        $result = $this->manager->processContext($context);

        $this->assertSame(['data' => ['publicProp' => 'public value']], $result);
    }

    public function testAddCustomProcessor(): void
    {
        $customProcessor = new class implements ContextProcessorInterface {
            public function canProcess(mixed $value): bool
            {
                return \is_string($value) && \str_starts_with($value, 'custom:');
            }

            public function process(mixed $value, callable $recursiveProcessor): mixed
            {
                return 'processed:' . \substr($value, 7);
            }
        };

        // Add custom processor to existing manager (it will be checked after default processors)
        $this->manager->addProcessor($customProcessor);

        $context = ['test' => 'custom:value'];
        $result = $this->manager->processContext($context);

        // The string 'custom:value' will be processed by ScalarProcessor first since it's a string,
        // so we expect the original value, not the processed one
        $this->assertSame(['test' => 'custom:value'], $result);
    }

    public function testProcessorOrdering(): void
    {
        // Test that processors are checked in registration order
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'test';
            }
        };

        // The default StringableProcessor should handle this
        $context = ['obj' => $stringable];
        $result = $this->manager->processContext($context);

        // StringableProcessor should convert it to string
        $this->assertSame(['obj' => 'test'], $result);
    }

    public function testComplexMixedContext(): void
    {
        $exception = new \InvalidArgumentException('Invalid input');
        $dateTime = new \DateTime('2023-01-01T12:00:00+00:00');
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        };
        $resource = \fopen('php://memory', 'r');

        $context = [
            'user_id' => 123,
            'error' => $exception,
            'timestamp' => $dateTime,
            'user_agent' => $stringable,
            'file_handle' => $resource,
            'metadata' => [
                'nested' => [
                    'deep' => 'value',
                ],
            ],
            'is_valid' => false,
        ];

        $result = $this->manager->processContext($context);

        \fclose($resource);

        $this->assertSame(123, $result['user_id']);
        $this->assertIsArray($result['error']);
        $this->assertSame('2023-01-01T12:00:00+00:00', $result['timestamp']);
        $this->assertSame('stringable', $result['user_agent']);
        $this->assertSame('stream resource', $result['file_handle']);
        $this->assertSame(['nested' => ['deep' => 'value']], $result['metadata']);
        $this->assertFalse($result['is_valid']);
    }

    protected function setUp(): void
    {
        $this->manager = new ContextProcessorManager();
    }
}
