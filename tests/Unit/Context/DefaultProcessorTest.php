<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context;

use Testo\Data\DataProvider;
use Testo\Codecov\Covers;
use Testo\Test;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use RoadRunner\PsrLogger\Context\DefaultProcessor;

#[Covers(DefaultProcessor::class)]
#[Test]
final class DefaultProcessorTest
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
        Assert::same(($this->processor)($value), $expected);
    }

    public function testProcessNull(): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = ($this->processor)(null, $recursiveProcessor);
        Assert::null($result);
    }

    public function testProcessScalarValues(): void
    {
        $values = ['test string', 42, 3.14, true, false];
        $recursiveProcessor = static fn($v) => $v;

        foreach ($values as $value) {
            $result = ($this->processor)($value, $recursiveProcessor);
            Assert::same($result, $value);
        }
    }

    public function testProcessSimpleArray(): void
    {
        $array = [1, 2, 'three', true];
        $recursiveProcessor = static fn($v) => $v; // Identity function for simple values

        $result = ($this->processor)($array, $recursiveProcessor);

        Assert::same($result, [1, 2, 'three', true]);
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

        Assert::array($result)->hasKeys('level1');
        Assert::array($result['level1']);
        Assert::array($result['level1'])->hasKeys('level2');
        Assert::array($result['level1']['level2']);
        Assert::same($result['level1']['level2']['value'], 'deep');
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = DefaultProcessor::create();
    }
}
