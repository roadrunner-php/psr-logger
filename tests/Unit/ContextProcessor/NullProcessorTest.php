<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\ContextProcessor\NullProcessor;

#[CoversClass(NullProcessor::class)]
class NullProcessorTest extends TestCase
{
    private NullProcessor $processor;

    public static function nonNullValuesProvider(): array
    {
        return [
            'string' => ['test'],
            'integer' => [42],
            'float' => [3.14],
            'boolean true' => [true],
            'boolean false' => [false],
            'array' => [[]],
            'object' => [new \stdClass()],
            'resource' => [\fopen('php://memory', 'r')],
        ];
    }

    public function testCanProcessNull(): void
    {
        $this->assertTrue($this->processor->canProcess(null));
    }

    #[DataProvider('nonNullValuesProvider')]
    public function testCannotProcessNonNullValues(mixed $value): void
    {
        $this->assertFalse($this->processor->canProcess($value));
    }

    public function testProcessNull(): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process(null, $recursiveProcessor);

        $this->assertNull($result);
    }

    protected function setUp(): void
    {
        $this->processor = new NullProcessor();
    }
}
