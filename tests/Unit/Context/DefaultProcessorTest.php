<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context;

use RoadRunner\PsrLogger\Context\DefaultProcessor;
use RoadRunner\PsrLogger\Context\ObjectProcessor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Data\DataSet;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

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
            'nan' => [\NAN, 'NAN'],
            'infinity' => [\INF, 'INF'],
            'negative infinity' => [-\INF, '-INF'],
            'nested nan' => [['a' => ['b' => \NAN]], ['a' => ['b' => 'NAN']]],
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

    public function emptyProcessorLeavesObjectsUntouched(): void
    {
        $date = new \DateTimeImmutable('2024-02-03T04:05:06+00:00');

        $result = ($this->processor)(['date' => $date]);

        Assert::same($result['date'], $date);
    }

    public function defaultProcessorConvertsObjectsWithBuiltInProcessors(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'stringable';
            }
        };

        $result = DefaultProcessor::createDefault()([
            'date' => new \DateTimeImmutable('2024-02-03T04:05:06+00:00'),
            'text' => $stringable,
            'object' => (object) ['id' => 1],
        ]);

        Assert::same($result['date'], '2024-02-03T04:05:06+00:00');
        Assert::same($result['text'], 'stringable');
        Assert::same($result['object'], ['@class' => 'stdClass', 'id' => 1]);
    }

    public function defaultProcessorConvertsThrowableToStructuredArray(): void
    {
        $result = DefaultProcessor::createDefault()(new \RuntimeException('boom', 7));

        Assert::array($result)->hasKeys('file', 'line', 'trace');
        Assert::same($result['class'], \RuntimeException::class);
        Assert::same($result['message'], 'boom');
        Assert::same($result['code'], 7);
    }

    public function defaultProcessorConvertsErrorToStructuredArray(): void
    {
        $result = DefaultProcessor::createDefault()(new \TypeError('wrong type'));

        Assert::array($result);
        Assert::same($result['class'], \TypeError::class);
        Assert::same($result['message'], 'wrong type');
    }

    public function defaultProcessorPrefersStructuredArrayOverCustomToString(): void
    {
        $exception = new class('boom') extends \Exception {
            public function __toString(): string
            {
                return 'custom string';
            }
        };

        $result = DefaultProcessor::createDefault()(['error' => $exception]);

        Assert::array($result['error']);
        Assert::same($result['error']['class'], $exception::class);
        Assert::same($result['error']['message'], 'boom');
    }

    public function defaultProcessorConvertsObjectsInNestedArrays(): void
    {
        $result = DefaultProcessor::createDefault()([
            'level1' => ['level2' => ['date' => new \DateTimeImmutable('2024-02-03T04:05:06+00:00')]],
        ]);

        Assert::same($result, ['level1' => ['level2' => ['date' => '2024-02-03T04:05:06+00:00']]]);
    }

    public function defaultProcessorConvertsNestedObjectProperties(): void
    {
        $value = (object) [
            'createdAt' => new \DateTimeImmutable('2024-02-03T04:05:06+00:00'),
            'owner' => (object) ['id' => 1],
        ];

        $result = DefaultProcessor::createDefault()($value);

        Assert::same($result, [
            '@class' => 'stdClass',
            'createdAt' => '2024-02-03T04:05:06+00:00',
            'owner' => ['@class' => 'stdClass', 'id' => 1],
        ]);
    }

    public function customProcessorTakesPrecedenceOverBuiltIn(): void
    {
        $processor = DefaultProcessor::createDefault()
            ->withObjectProcessors(self::taggingProcessor('custom', \DateTimeInterface::class));

        $result = $processor(new \DateTimeImmutable());

        Assert::same($result, 'custom');
    }

    public function nonMatchingCustomProcessorFallsThroughToBuiltIn(): void
    {
        $processor = DefaultProcessor::createDefault()
            ->withObjectProcessors(self::taggingProcessor('custom', \Throwable::class));

        $result = $processor(new \DateTimeImmutable('2024-02-03T04:05:06+00:00'));

        Assert::same($result, '2024-02-03T04:05:06+00:00');
    }

    public function processorsOfOneCallKeepArgumentOrder(): void
    {
        $processor = DefaultProcessor::create()->withObjectProcessors(
            self::taggingProcessor('first', \stdClass::class),
            self::taggingProcessor('second', \stdClass::class),
        );

        $result = $processor(new \stdClass());

        Assert::same($result, 'first');
    }

    public function processorsOfLaterCallTakePrecedence(): void
    {
        $processor = DefaultProcessor::create()
            ->withObjectProcessors(self::taggingProcessor('first', \stdClass::class))
            ->withObjectProcessors(self::taggingProcessor('second', \stdClass::class));

        $result = $processor(new \stdClass());

        Assert::same($result, 'second');
    }

    public function withObjectProcessorsKeepsOriginalUnchanged(): void
    {
        $original = DefaultProcessor::create();

        $extended = $original->withObjectProcessors(self::taggingProcessor('custom', \stdClass::class));
        $object = new \stdClass();

        Assert::notSame($extended, $original);
        Assert::same($original($object), $object);
        Assert::same($extended($object), 'custom');
    }

    public function keepsTenNestingLevels(): void
    {
        Assert::same(($this->processor)(self::nest(10)), self::nest(10));
    }

    public function replacesEleventhNestingLevelWithNotice(): void
    {
        Assert::same(
            ($this->processor)(self::nest(11)),
            self::nest(10, 'Over 10 levels deep, aborting normalization'),
        );
    }

    public function limitsNestingOfObjects(): void
    {
        $processor = DefaultProcessor::createDefault()->withMaxDepth(2);
        $object = (object) ['child' => (object) ['child' => (object) ['child' => null]]];

        Assert::same($processor(['o' => $object]), [
            'o' => [
                '@class' => \stdClass::class,
                'child' => 'Over 2 levels deep, aborting normalization',
            ],
        ]);
    }

    public function stopsOnSelfReferencingArray(): void
    {
        $value = ['a' => 1];
        $value['self'] = &$value;

        $result = DefaultProcessor::create()->withMaxDepth(2)($value);

        Assert::same($result, [
            'a' => 1,
            'self' => ['a' => 1, 'self' => 'Over 2 levels deep, aborting normalization'],
        ]);
    }

    public function restoresDepthAfterProcessorThrows(): void
    {
        $processor = DefaultProcessor::create()
            ->withMaxDepth(2)
            ->withObjectProcessors(new class implements ObjectProcessor {
                public function canProcess(object $value): bool
                {
                    return true;
                }

                public function process(object $value, callable $processor): mixed
                {
                    throw new \RuntimeException('boom');
                }
            });

        Expect::exception(\RuntimeException::class);
        try {
            $processor([new \stdClass()]);
        } finally {
            Assert::same($processor([['x']]), [['x']]);
        }
    }

    public function namedArgumentsDoNotLetLaterCallDropEarlierProcessors(): void
    {
        $processor = DefaultProcessor::create()
            ->withObjectProcessors(processor: self::taggingProcessor('first', \stdClass::class))
            ->withObjectProcessors(processor: self::taggingProcessor('second', \DateTimeImmutable::class));

        Assert::same($processor(new \stdClass()), 'first');
        Assert::same($processor(new \DateTimeImmutable()), 'second');
    }

    public function keepsThousandItems(): void
    {
        $items = \range(1, 1000);

        Assert::same(($this->processor)($items), $items);
    }

    public function replacesItemsOverThousandWithNotice(): void
    {
        $result = ($this->processor)(\range(1, 1002));

        Assert::count($result, 1001);
        Assert::same($result[999], 1000);
        Assert::same($result['...'], 'Over 1000 items (1002 total), aborting normalization');
    }

    public function withMaxItemsLimitsEveryArray(): void
    {
        $processor = DefaultProcessor::create()->withMaxItems(1);

        Assert::same($processor(['a' => ['b' => 1, 'c' => 2], 'd' => 3]), [
            'a' => ['b' => 1, '...' => 'Over 1 items (2 total), aborting normalization'],
            '...' => 'Over 1 items (2 total), aborting normalization',
        ]);
    }

    public function withLimitsKeepsOriginalUnchanged(): void
    {
        $original = DefaultProcessor::create();

        $original->withMaxDepth(1);
        $original->withMaxItems(1);

        Assert::same($original(self::nest(10)), self::nest(10));
        Assert::same($original([1, 2]), [1, 2]);
    }

    #[DataSet([0], 'zero')]
    #[DataSet([-1], 'negative')]
    public function withMaxDepthRejectsLimitBelowOne(int $levels): void
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage("Max depth must be at least 1, $levels given.");

        DefaultProcessor::create()->withMaxDepth($levels);
    }

    public function withMaxDepthAcceptsOneLevel(): void
    {
        Assert::same(DefaultProcessor::create()->withMaxDepth(1)(['a' => []]), [
            'a' => 'Over 1 levels deep, aborting normalization',
        ]);
    }

    #[DataSet([0], 'zero')]
    #[DataSet([-1], 'negative')]
    public function withMaxItemsRejectsLimitBelowOne(int $items): void
    {
        Expect::exception(\InvalidArgumentException::class)
            ->withMessage("Max items must be at least 1, $items given.");

        DefaultProcessor::create()->withMaxItems($items);
    }

    public function copyMadeDuringProcessingStartsAtTopLevel(): void
    {
        $copier = new class implements ObjectProcessor {
            public ?DefaultProcessor $copy = null;

            public function canProcess(object $value): bool
            {
                return true;
            }

            public function process(object $value, callable $processor): mixed
            {
                \assert($processor instanceof DefaultProcessor);
                $this->copy = $processor->withMaxItems(5);
                return null;
            }
        };

        DefaultProcessor::create()->withObjectProcessors($copier)(self::nest(9, new \stdClass()));

        Assert::same($copier->copy?->__invoke(self::nest(10)), self::nest(10));
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = DefaultProcessor::create();
    }

    /**
     * @param int<0, max> $levels Number of arrays wrapped around the leaf.
     */
    private static function nest(int $levels, mixed $leaf = 'leaf'): mixed
    {
        $value = $leaf;
        for ($i = 0; $i < $levels; ++$i) {
            $value = ['x' => $value];
        }

        return $value;
    }

    /**
     * @param class-string $class
     */
    private static function taggingProcessor(string $tag, string $class): ObjectProcessor
    {
        return new class($tag, $class) implements ObjectProcessor {
            public function __construct(
                private readonly string $tag,
                private readonly string $class,
            ) {}

            public function canProcess(object $value): bool
            {
                return $value instanceof $this->class;
            }

            public function process(object $value, callable $processor): mixed
            {
                return $this->tag;
            }
        };
    }
}
