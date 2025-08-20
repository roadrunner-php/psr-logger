<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\ContextProcessor\ArrayProcessor;

#[CoversClass(ArrayProcessor::class)]
class ArrayProcessorTest extends TestCase
{
    private ArrayProcessor $processor;

    public static function nonArrayProvider(): array
    {
        return [
            'string' => ['not an array'],
            'integer' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'null' => [null],
            'object' => [new \stdClass()],
            'resource' => [\fopen('php://memory', 'r')],
        ];
    }

    public function testCanProcessArray(): void
    {
        $this->assertTrue($this->processor->canProcess([]));
        $this->assertTrue($this->processor->canProcess([1, 2, 3]));
        $this->assertTrue($this->processor->canProcess(['key' => 'value']));
    }

    #[DataProvider('nonArrayProvider')]
    public function testCannotProcessNonArray(mixed $value): void
    {
        $this->assertFalse($this->processor->canProcess($value));
    }

    public function testProcessEmptyArray(): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process([], $recursiveProcessor);

        $this->assertSame([], $result);
    }

    public function testProcessSimpleArray(): void
    {
        $array = [1, 2, 'three', true];
        $recursiveProcessor = static fn($v) => $v; // Identity function for simple values

        $result = $this->processor->process($array, $recursiveProcessor);

        $this->assertSame([1, 2, 'three', true], $result);
    }

    public function testProcessAssociativeArray(): void
    {
        $array = [
            'string' => 'value',
            'number' => 42,
            'boolean' => false,
        ];
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($array, $recursiveProcessor);

        $this->assertSame($array, $result);
    }

    public function testProcessNestedArray(): void
    {
        $array = [
            'level1' => [
                'level2' => [
                    'value' => 'deep',
                ],
            ],
        ];

        // Mock recursive processor that adds a prefix to strings
        $recursiveProcessor = static function ($value) {
            return \is_string($value) ? 'processed:' . $value : $value;
        };

        $result = $this->processor->process($array, $recursiveProcessor);

        $expected = [
            'level1' => 'processed:' . \serialize(['level2' => ['value' => 'deep']]),
        ];

        // Since the recursive processor is called on the nested array,
        // we need to test that it was called correctly
        $this->assertArrayHasKey('level1', $result);
    }

    public function testProcessArrayWithMixedTypes(): void
    {
        $array = [
            'string' => 'test',
            'number' => 123,
            'null' => null,
            'array' => ['nested'],
        ];

        $recursiveProcessor = static function ($value) {
            if (\is_string($value)) {
                return \strtoupper($value);
            }
            if (\is_array($value)) {
                return 'array_processed';
            }
            return $value;
        };

        $result = $this->processor->process($array, $recursiveProcessor);

        $this->assertSame('TEST', $result['string']);
        $this->assertSame(123, $result['number']);
        $this->assertNull($result['null']);
        $this->assertSame('array_processed', $result['array']);
    }

    public function testProcessArrayPreservesKeys(): void
    {
        $array = [
            'first' => 1,
            'second' => 2,
            0 => 'zero',
            1 => 'one',
        ];

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($array, $recursiveProcessor);

        $this->assertArrayHasKey('first', $result);
        $this->assertArrayHasKey('second', $result);
        $this->assertArrayHasKey(0, $result);
        $this->assertArrayHasKey(1, $result);
        $this->assertSame(1, $result['first']);
        $this->assertSame(2, $result['second']);
        $this->assertSame('zero', $result[0]);
        $this->assertSame('one', $result[1]);
    }

    protected function setUp(): void
    {
        $this->processor = new ArrayProcessor();
    }
}
