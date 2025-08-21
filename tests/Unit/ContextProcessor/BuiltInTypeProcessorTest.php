<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Internal\ContextProcessor\BuiltInTypeProcessor;

#[CoversClass(BuiltInTypeProcessor::class)]
class BuiltInTypeProcessorTest extends TestCase
{
    private BuiltInTypeProcessor $processor;

    public static function builtInTypeValuesProvider(): array
    {
        return [
            'string' => ['test string', true],
            'integer' => [42, true],
            'float' => [3.14, true],
            'boolean true' => [true, true],
            'boolean false' => [false, true],
            'null' => [null, true], // null is now handled by BuiltInTypeProcessor
            'empty array' => [[], true],
            'simple array' => [[1, 2, 3], true],
            'associative array' => [['key' => 'value'], true],
            'object' => [new \stdClass(), false],
            'resource' => [\fopen('php://memory', 'r'), false],
        ];
    }

    #[DataProvider('builtInTypeValuesProvider')]
    public function testCanProcessBuiltInTypes(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, $this->processor->canProcess($value));
    }

    public function testProcessNull(): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process(null, $recursiveProcessor);
        $this->assertNull($result);
    }

    public function testProcessScalarValues(): void
    {
        $values = ['test string', 42, 3.14, true, false];
        $recursiveProcessor = static fn($v) => $v;

        foreach ($values as $value) {
            $result = $this->processor->process($value, $recursiveProcessor);
            $this->assertSame($value, $result);
        }
    }

    public function testProcessSimpleArray(): void
    {
        $array = [1, 2, 'three', true];
        $recursiveProcessor = static fn($v) => $v; // Identity function for simple values

        $result = $this->processor->process($array, $recursiveProcessor);

        $this->assertSame([1, 2, 'three', true], $result);
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

        // Mock recursive processor that adds a prefix to strings and handles arrays recursively
        $recursiveProcessor = static function ($value) use (&$recursiveProcessor) {
            if (\is_string($value)) {
                return 'processed:' . $value;
            }
            if (\is_array($value)) {
                $processed = [];
                foreach ($value as $key => $item) {
                    $processed[$key] = $recursiveProcessor($item);
                }
                return $processed;
            }
            return $value;
        };

        $result = $this->processor->process($array, $recursiveProcessor);

        $this->assertArrayHasKey('level1', $result);
        $this->assertIsArray($result['level1']);
        $this->assertArrayHasKey('level2', $result['level1']);
        $this->assertIsArray($result['level1']['level2']);
        $this->assertSame('processed:deep', $result['level1']['level2']['value']);
    }

    protected function setUp(): void
    {
        $this->processor = new BuiltInTypeProcessor();
    }
}
