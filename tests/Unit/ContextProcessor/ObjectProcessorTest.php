<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\ContextProcessor;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RoadRunner\PsrLogger\ContextProcessor\ObjectProcessor;

#[CoversClass(ObjectProcessor::class)]
class ObjectProcessorTest extends TestCase
{
    private ObjectProcessor $processor;

    public static function nonObjectProvider(): array
    {
        return [
            'string' => ['not an object'],
            'integer' => [42],
            'float' => [3.14],
            'boolean' => [true],
            'null' => [null],
            'array' => [[]],
            'resource' => [\fopen('php://memory', 'r')],
        ];
    }

    public function testCanProcessObject(): void
    {
        $this->assertTrue($this->processor->canProcess(new \stdClass()));
        $this->assertTrue($this->processor->canProcess(new \DateTime()));
    }

    #[DataProvider('nonObjectProvider')]
    public function testCannotProcessNonObject(mixed $value): void
    {
        $this->assertFalse($this->processor->canProcess($value));
    }

    public function testProcessObjectWithPublicProperties(): void
    {
        $object = new class {
            public string $name = 'test';
            public int $age = 25;
            public bool $active = true;
            private string $secret = 'hidden';
        };

        $recursiveProcessor = static fn($v) => \is_string($v) ? \strtoupper($v) : $v;
        $result = $this->processor->process($object, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertSame('TEST', $result['name']); // Processed by recursive processor
        $this->assertSame(25, $result['age']);
        $this->assertTrue($result['active']);
        $this->assertArrayNotHasKey('secret', $result); // Private property not included
    }

    public function testProcessObjectWithoutPublicProperties(): void
    {
        $object = new class {
            private string $private = 'hidden';
            protected string $protected = 'also hidden';
        };

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($object, $recursiveProcessor);

        // Should return class name when no public properties
        $this->assertIsString($result);
        $this->assertTrue(\str_contains($result, 'anonymous'));
    }

    public function testProcessStdClass(): void
    {
        $object = new \stdClass();
        $object->property1 = 'value1';
        $object->property2 = 42;

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($object, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertSame('value1', $result['property1']);
        $this->assertSame(42, $result['property2']);
    }

    public function testProcessEmptyStdClass(): void
    {
        $object = new \stdClass();

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($object, $recursiveProcessor);

        // Empty stdClass has no public properties, should return class name
        $this->assertSame('stdClass', $result);
    }

    public function testProcessObjectWithNestedData(): void
    {
        $object = new class {
            public array $data = ['nested' => 'value'];
            public object $nested;

            public function __construct()
            {
                $this->nested = new \stdClass();
                $this->nested->prop = 'nested prop';
            }
        };

        $recursiveProcessor = static function ($value) {
            if (\is_array($value)) {
                return 'processed_array';
            }
            if (\is_object($value)) {
                return 'processed_object';
            }
            return $value;
        };

        $result = $this->processor->process($object, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertSame('processed_array', $result['data']);
        $this->assertSame('processed_object', $result['nested']);
    }

    public function testProcessNamedClass(): void
    {
        $object = new \DateTime('2023-01-01');

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($object, $recursiveProcessor);

        // DateTime has no public properties, should return class name
        $this->assertSame('DateTime', $result);
    }

    public function testProcessObjectWithMixedPropertyTypes(): void
    {
        $object = new class {
            public ?string $nullableString = null;
            public string $emptyString = '';
            public int $zero = 0;
            public bool $false = false;
            public array $emptyArray = [];
        };

        $recursiveProcessor = static fn($v) => $v;
        $result = $this->processor->process($object, $recursiveProcessor);

        $this->assertIsArray($result);
        $this->assertNull($result['nullableString']);
        $this->assertSame('', $result['emptyString']);
        $this->assertSame(0, $result['zero']);
        $this->assertFalse($result['false']);
        $this->assertSame([], $result['emptyArray']);
    }

    protected function setUp(): void
    {
        $this->processor = new ObjectProcessor();
    }
}
