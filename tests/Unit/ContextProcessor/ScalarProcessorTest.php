<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\ContextProcessor\ScalarProcessor;

#[CoversClass(ScalarProcessor::class)]
class ScalarProcessorTest extends TestCase
{
    private ScalarProcessor $processor;

    public static function scalarValuesProvider(): array
    {
        return [
            'string' => ['test string', true],
            'integer' => [42, true],
            'float' => [3.14, true],
            'boolean true' => [true, true],
            'boolean false' => [false, true],
            'null' => [null, false], // null is now handled by NullProcessor
            'array' => [[], false],
            'object' => [new \stdClass(), false],
            'resource' => [\fopen('php://memory', 'r'), false],
        ];
    }

    #[DataProvider('scalarValuesProvider')]
    public function testCanProcessScalarValues(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, $this->processor->canProcess($value));
    }

    #[DataProvider('scalarValuesProvider')]
    public function testProcessScalarValues(mixed $value, bool $canProcess): void
    {
        if ($canProcess) {
            $recursiveProcessor = static fn($v) => $v;
            $result = $this->processor->process($value, $recursiveProcessor);
            $this->assertSame($value, $result);
        } else {
            $this->addToAssertionCount(1); // Skip non-scalar values
        }
    }

    protected function setUp(): void
    {
        $this->processor = new ScalarProcessor();
    }
}
