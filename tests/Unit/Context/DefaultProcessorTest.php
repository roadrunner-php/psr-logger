<?php

declare(strict_types=1);

namespace Context;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Context\DefaultProcessor;

#[CoversClass(DefaultProcessor::class)]
class DefaultProcessorTest extends TestCase
{
    private DefaultProcessor $processor;

    public static function builtInTypeValuesProvider(): array
    {
        return [
            'string' => ['test string', 'test string'],
            'integer' => [42, 42],
            'float' => [3.14, 3.14],
            'boolean true' => [true, true],
            'boolean false' => [false, false],
            'null' => [null, null],
            'empty array' => [[], []],
            'simple array' => [[1, 2, 3], [1, 2, 3]],
            'associative array' => [['key' => 'value'], ['key' => 'value']],
            'resource' => [\fopen('php://memory', 'r'), 'stream resource'],
        ];
    }

    #[DataProvider('builtInTypeValuesProvider')]
    public function testCanProcessBuiltInTypes(mixed $value, mixed $expected): void
    {
        $this->assertSame($expected, ($this->processor)($value));
    }

    public function testProcessNull(): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = ($this->processor)(null, $recursiveProcessor);
        $this->assertNull($result);
    }

    public function testProcessScalarValues(): void
    {
        $values = ['test string', 42, 3.14, true, false];
        $recursiveProcessor = static fn($v) => $v;

        foreach ($values as $value) {
            $result = ($this->processor)($value, $recursiveProcessor);
            $this->assertSame($value, $result);
        }
    }

    public function testProcessSimpleArray(): void
    {
        $array = [1, 2, 'three', true];
        $recursiveProcessor = static fn($v) => $v; // Identity function for simple values

        $result = ($this->processor)($array, $recursiveProcessor);

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

        $result = ($this->processor)($array);

        $this->assertArrayHasKey('level1', $result);
        $this->assertIsArray($result['level1']);
        $this->assertArrayHasKey('level2', $result['level1']);
        $this->assertIsArray($result['level1']['level2']);
        $this->assertSame('deep', $result['level1']['level2']['value']);
    }

    protected function setUp(): void
    {
        $this->processor = DefaultProcessor::create();
    }
}
