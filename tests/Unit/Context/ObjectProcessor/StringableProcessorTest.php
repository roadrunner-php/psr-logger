<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use RoadRunner\PsrLogger\Context\ObjectProcessor\StringableProcessor;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Data\DataProvider;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

#[Covers(StringableProcessor::class)]
#[Test]
final class StringableProcessorTest
{
    private StringableProcessor $processor;

    public static function nonStringableProvider(): array
    {
        return [
            [new \stdClass(), false],
            [new class implements \Stringable {
                public function __toString(): string
                {
                    return 'I am string';
                }
            }, true],
        ];
    }

    public function testCanProcessStringable(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'test string';
            }
        };

        Assert::true($this->processor->canProcess($stringable));
    }

    #[DataProvider('nonStringableProvider')]
    public function testCannotProcessNonStringable(mixed $value, $expected): void
    {
        Assert::same($this->processor->canProcess($value), $expected);
    }

    public function testProcessStringable(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return 'converted string';
            }
        };

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($stringable, $recursiveProcessor);

        Assert::same($result, 'converted string');
    }

    public function testProcessStringableWithComplexLogic(): void
    {
        $stringable = new class implements \Stringable {
            private string $data = 'complex data';

            public function __toString(): string
            {
                return \strtoupper($this->data);
            }
        };

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($stringable, $recursiveProcessor);

        Assert::same($result, 'COMPLEX DATA');
    }

    public function testProcessStringableWithEmptyString(): void
    {
        $stringable = new class implements \Stringable {
            public function __toString(): string
            {
                return '';
            }
        };

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($stringable, $recursiveProcessor);

        Assert::same($result, '');
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = new StringableProcessor();
    }
}
