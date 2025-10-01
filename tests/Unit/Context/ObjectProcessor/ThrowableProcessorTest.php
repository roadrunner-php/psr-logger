<?php

declare(strict_types=1);

namespace Context\ObjectProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Context\ObjectProcessor\ThrowableProcessor;

#[CoversClass(ThrowableProcessor::class)]
class ThrowableProcessorTest extends TestCase
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
        $this->assertTrue($this->processor->canProcess($throwable));
    }

    #[DataProvider('nonThrowableProvider')]
    public function testCannotProcessNonThrowable(mixed $value): void
    {
        $this->assertFalse($this->processor->canProcess($value));
    }

    public function testProcessException(): void
    {
        $exception = new \RuntimeException('Test error message', 500);
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($exception, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertSame('RuntimeException', $result['class']);
        $this->assertSame('Test error message', $result['message']);
        $this->assertSame(500, $result['code']);
        $this->assertArrayHasKey('file', $result);
        $this->assertArrayHasKey('line', $result);
        $this->assertArrayHasKey('trace', $result);
        $this->assertIsString($result['file']);
        $this->assertIsInt($result['line']);
        $this->assertIsString($result['trace']);
    }

    public function testProcessError(): void
    {
        $error = new \Error('Test error', 123);
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($error, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertSame('Error', $result['class']);
        $this->assertSame('Test error', $result['message']);
        $this->assertSame(123, $result['code']);
    }

    public function testProcessCustomException(): void
    {
        $customException = new class('Custom message', 999) extends \Exception {};
        $recursiveProcessor = static fn($v) => $v;

        $result = $this->processor->process($customException, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertTrue(\str_contains($result['class'], 'Exception@anonymous'));
        $this->assertSame('Custom message', $result['message']);
        $this->assertSame(999, $result['code']);
    }

    protected function setUp(): void
    {
        $this->processor = new ThrowableProcessor();
    }
}
