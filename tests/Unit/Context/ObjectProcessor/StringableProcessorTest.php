<?php

declare(strict_types=1);

namespace Context\ObjectProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\Context\ObjectProcessor\StringableProcessor;

#[CoversClass(StringableProcessor::class)]
class StringableProcessorTest extends TestCase
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

        $this->assertTrue($this->processor->canProcess($stringable));
    }

    #[DataProvider('nonStringableProvider')]
    public function testCannotProcessNonStringable(mixed $value, $expected): void
    {
        $this->assertSame($expected, $this->processor->canProcess($value));
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

        $this->assertSame('converted string', $result);
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

        $this->assertSame('COMPLEX DATA', $result);
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

        $this->assertSame('', $result);
    }

    protected function setUp(): void
    {
        $this->processor = new StringableProcessor();
    }
}
