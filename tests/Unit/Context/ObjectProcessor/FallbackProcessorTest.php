<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use Testo\Data\DataProvider;
use Testo\Codecov\Covers;
use Testo\Test;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use RoadRunner\PsrLogger\Context\ObjectProcessor\FallbackProcessor;

#[Covers(FallbackProcessor::class)]
#[Test]
final class FallbackProcessorTest
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
        Assert::true($this->processor->canProcess($value));
        Assert::same($result, $expectedType);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = new FallbackProcessor();
    }
}
