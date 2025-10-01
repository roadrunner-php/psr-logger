<?php

declare(strict_types=1);

namespace Context\ObjectProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Context\ObjectProcessor\FallbackProcessor;

#[CoversClass(FallbackProcessor::class)]
class FallbackProcessorTest extends TestCase
{
    private FallbackProcessor $processor;

    public static function allTypesProvider(): array
    {
        $resource = \fopen('php://memory', 'r');

        $data = [
            'object' => [new \stdClass(), [
                '@class' => 'stdClass',
            ]],
            'object with props' => [(object) ['foo' => 'bar'], [
                '@class' => 'stdClass',
                'foo' => 'bar',
            ]],
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
    public function testProcessReturnsTypeString(object $value, mixed $expectedType): void
    {
        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($value, $recursiveProcessor);

        // FallbackProcessor should be able to process any object
        $this->assertTrue($this->processor->canProcess($value));
        $this->assertSame($expectedType, $result);
    }

    protected function setUp(): void
    {
        $this->processor = new FallbackProcessor();
    }
}
