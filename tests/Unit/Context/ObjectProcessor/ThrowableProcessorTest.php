<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor\ThrowableProcessor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Data\DataSet;
use Testo\Expect;
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

    public function traceKeepsEightFramesByDefault(): void
    {
        $exception = self::thrownAtDepth(10);

        $lines = \explode("\n", $this->processor->process($exception, static fn($v) => $v)['trace']);

        Assert::count($lines, 9);
        Assert::same($lines[8], \sprintf('... %d more', \count($exception->getTrace()) - 8));
    }

    public function traceFormatsFramesWithoutArguments(): void
    {
        $exception = self::thrownAtDepth(2);
        $frame = $exception->getTrace()[0];

        $lines = \explode("\n", (new ThrowableProcessor(2))->process($exception, static fn($v) => $v)['trace']);

        Assert::same($lines[0], \sprintf('#0 %s(%d): %s::throwAtDepth()', __FILE__, $frame['line'], self::class));
        Assert::same($lines[1], \sprintf('#1 %s(%d): %s::throwAtDepth()', __FILE__, $exception->getTrace()[1]['line'], self::class));
        Assert::same($lines[2], \sprintf('... %d more', \count($exception->getTrace()) - 2));
    }

    public function traceMarksFramesWithoutFileAsInternal(): void
    {
        try {
            \array_map(static fn() => throw new \RuntimeException('boom'), [1]);
        } catch (\RuntimeException $exception) {
        }

        $trace = (new ThrowableProcessor(1))->process($exception, static fn($v) => $v)['trace'];

        Assert::true(\str_starts_with($trace, '#0 [internal function]: '));
    }

    public function traceKeepsEveryFrameUpToLimit(): void
    {
        $exception = new \RuntimeException('boom');

        $frames = \count($exception->getTrace());

        $trace = (new ThrowableProcessor($frames))->process($exception, static fn($v) => $v)['trace'];

        Assert::count(\explode("\n", $trace), $frames + 1);
        Assert::false(\str_contains($trace, 'more'));
    }

    public function traceCountsSingleDroppedFrame(): void
    {
        $exception = self::thrownAtDepth(3);
        $frames = \count($exception->getTrace());

        $lines = \explode("\n", (new ThrowableProcessor($frames - 1))->process($exception, static fn($v) => $v)['trace']);

        Assert::count($lines, $frames);
        Assert::same($lines[$frames - 1], '... 1 more');
    }

    public function traceMatchesPhpTraceWhenNothingIsCut(): void
    {
        $exception = self::thrownAtDepth(3);
        self::removeArguments($exception);

        $trace = (new ThrowableProcessor(\count($exception->getTrace())))->process($exception, static fn($v) => $v)['trace'];

        Assert::same($trace, $exception->getTraceAsString());
    }

    public function traceOfExceptionWithoutFramesIsMain(): void
    {
        $exception = new \RuntimeException('boom');
        self::setTrace($exception, []);

        Assert::same($this->processor->process($exception, static fn($v) => $v)['trace'], '#0 {main}');
    }

    public function traceWritesFileWithoutLineAsLineZero(): void
    {
        $exception = new \RuntimeException('boom');
        self::setTrace($exception, [['file' => '/app/x.php', 'function' => 'f']]);

        Assert::same($this->processor->process($exception, static fn($v) => $v)['trace'], "#0 /app/x.php(0): f()\n#1 {main}");
    }

    #[DataSet([0], 'zero')]
    #[DataSet([-1], 'negative')]
    public function rejectsTraceFramesBelowOne(int $frames): void
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage("Trace frames must be at least 1, $frames given.");

        new ThrowableProcessor($frames);
    }

    public function previousIsProcessedRecursively(): void
    {
        $exception = new \RuntimeException('outer', 0, new \LogicException('inner'));

        $result = $this->processor->process($exception, static fn(\Throwable $e): string => 'processed ' . $e->getMessage());

        Assert::same($result['previous'], 'processed inner');
    }

    public function previousIsAbsentWithoutPreviousException(): void
    {
        $result = $this->processor->process(new \RuntimeException('alone'), static fn($v) => $v);

        Assert::false(\array_key_exists('previous', $result));
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = new ThrowableProcessor();
    }

    private static function setTrace(\Throwable $exception, array $trace): void
    {
        (new \ReflectionProperty($exception instanceof \Exception ? \Exception::class : \Error::class, 'trace'))
            ->setValue($exception, $trace);
    }

    private static function removeArguments(\Throwable $exception): void
    {
        self::setTrace($exception, \array_map(
            static function (array $frame): array {
                unset($frame['args']);
                return $frame;
            },
            $exception->getTrace(),
        ));
    }

    /**
     * @param positive-int $depth Frames of {@see throwAtDepth()} on top of the trace.
     */
    private static function thrownAtDepth(int $depth): \RuntimeException
    {
        try {
            self::throwAtDepth($depth);
        } catch (\RuntimeException $e) {
            return $e;
        }

        throw new \LogicException('Not thrown');
    }

    /**
     * @param positive-int $depth
     */
    private static function throwAtDepth(int $depth): void
    {
        if ($depth === 1) {
            throw new \RuntimeException('deep');
        }

        self::throwAtDepth($depth - 1);
    }
}
