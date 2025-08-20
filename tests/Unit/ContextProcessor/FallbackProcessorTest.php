<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\ContextProcessor\FallbackProcessor;

#[CoversClass(FallbackProcessor::class)]
class FallbackProcessorTest extends TestCase
{
    private FallbackProcessor $processor;

    public static function allTypesProvider(): array
    {
        $resource = \fopen('php://memory', 'r');

        $data = [
            'string' => ['test string', 'string'],
            'integer' => [42, 'integer'],
            'float' => [3.14, 'double'], // PHP returns 'double' for floats
            'boolean true' => [true, 'boolean'],
            'boolean false' => [false, 'boolean'],
            'null' => [null, 'NULL'],
            'array' => [[], 'array'],
            'object' => [new \stdClass(), 'object'],
            'resource' => [$resource, 'resource'],
        ];

        // Close the resource after creating the test data
        \register_shutdown_function(static function () use ($resource): void {
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        });

        return $data;
    }

    #[DataProvider('allTypesProvider')]
    public function testCanProcessAnything(mixed $value): void
    {
        // FallbackProcessor should be able to process any value
        $this->assertTrue($this->processor->canProcess($value));
    }

    #[DataProvider('allTypesProvider')]
    public function testProcessReturnsTypeString(mixed $value, string $expectedType): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($value, $recursiveProcessor);

        $this->assertSame($expectedType, $result);
    }

    public function testProcessUnknownType(): void
    {
        // Create a resource and then close it to potentially get an "unknown type" or "resource (closed)"
        $resource = \fopen('php://memory', 'r');
        \fclose($resource);

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($resource, $recursiveProcessor);

        // The result should be a string representation of the type
        $this->assertIsString($result);
    }

    protected function setUp(): void
    {
        $this->processor = new FallbackProcessor();
    }
}
