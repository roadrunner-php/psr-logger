<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor\ThrowableProcessor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Covers(ThrowableProcessor::class)]
#[Test]
final class ThrowableProcessorTest
{
    private ThrowableProcessor $processor;

    public static function throwableProvider(): array
    {
        return [
            'Exception' => [new \Exception('test')],
            'RuntimeException' => [new \RuntimeException('test')],
            'InvalidArgumentException' => [new \InvalidArgumentException('test')],
            'Error' => [new \Error('test')],
            'TypeError' => [new \TypeError('test')],
        ];
    }

    public static function nonThrowableProvider(): array
    {
        return [
            'object' => [new \stdClass()],
        ];
    }

    #[DataProvider('throwableProvider')]
    public function testCanProcessThrowable(\Throwable $throwable): void
    {
        Assert::true($this->processor->canProcess($throwable));
    }

    #[DataProvider('nonThrowableProvider')]
    public function testCannotProcessNonThrowable(mixed $value): void
    {
        Assert::false($this->processor->canProcess($value));
    }

    public function testProcessException(): void
    {
        $exception = new \RuntimeException('Test error message', 500);
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($exception, $recursiveProcessor);

        Assert::array($result);
        Assert::same($result['class'], 'RuntimeException');
        Assert::same($result['message'], 'Test error message');
        Assert::same($result['code'], 500);
        Assert::array($result)->hasKeys('file')->hasKeys('line')->hasKeys('trace');
        Assert::string($result['file']);
        Assert::int($result['line']);
        Assert::string($result['trace']);
    }

    public function testProcessError(): void
    {
        $error = new \Error('Test error', 123);
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($error, $recursiveProcessor);

        Assert::array($result);
        Assert::same($result['class'], 'Error');
        Assert::same($result['message'], 'Test error');
        Assert::same($result['code'], 123);
    }

    public function testProcessCustomException(): void
    {
        $customException = new class('Custom message', 999) extends \Exception {};
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($customException, $recursiveProcessor);

        Assert::array($result);
        Assert::true(\str_contains($result['class'], 'Exception@anonymous'));
        Assert::same($result['message'], 'Custom message');
        Assert::same($result['code'], 999);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = new ThrowableProcessor();
    }
}
