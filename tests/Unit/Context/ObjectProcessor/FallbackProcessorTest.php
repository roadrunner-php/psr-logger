<?php

declare(strict_types=1);

namespace RoadRunner\PsrLogger\Tests\Unit\Context\ObjectProcessor;

use Testo\Data\DataProvider;
use Testo\Codecov\Covers;
use Testo\Test;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use RoadRunner\PsrLogger\Context\DefaultProcessor;
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

    public function exportsPublicPropertiesOnly(): void
    {
        $value = new class {
            public string $public = 'public';
            protected string $protected = 'protected';
            private string $private = 'private';
        };

        $result = $this->processor->process($value, static fn(mixed $v): mixed => $v);

        Assert::same($result, ['@class' => $value::class, 'public' => 'public']);
    }

    public function passesPropertyValuesThroughProcessor(): void
    {
        $value = (object) ['count' => 2, 'name' => 'test'];

        $result = $this->processor->process($value, static fn(mixed $v): mixed => \is_int($v) ? $v * 10 : $v);

        Assert::same($result, ['@class' => 'stdClass', 'count' => 20, 'name' => 'test']);
    }

    public function dropsPropertyReferencingTheObjectItself(): void
    {
        $value = new \stdClass();
        $value->name = 'node';
        $value->self = $value;
        $value->after = 'kept';

        $result = $this->processor->process($value, static fn(mixed $v): mixed => $v);

        Assert::same($result, ['@class' => 'stdClass', 'name' => 'node', 'after' => 'kept']);
    }

    public function doesNotPassSelfReferenceToProcessor(): void
    {
        $value = new \stdClass();
        $value->self = $value;
        $seen = [];

        $this->processor->process($value, static function (mixed $v) use (&$seen): mixed {
            $seen[] = $v;
            return $v;
        });

        Assert::false(\in_array($value, $seen, true));
    }

    public function selfReferenceTerminatesWithDefaultProcessor(): void
    {
        $value = new \stdClass();
        $value->name = 'node';
        $value->self = $value;

        $result = DefaultProcessor::createDefault()($value);

        Assert::same($result, ['@class' => 'stdClass', 'name' => 'node']);
    }

    public function dropsPropertyReferencingAnOwner(): void
    {
        $parent = new \stdClass();
        $child = new \stdClass();
        $parent->name = 'parent';
        $parent->child = $child;
        $child->name = 'child';
        $child->parent = $parent;

        $result = DefaultProcessor::createDefault()($parent);

        Assert::same($result, [
            '@class' => 'stdClass',
            'name' => 'parent',
            'child' => ['@class' => 'stdClass', 'name' => 'child'],
        ]);
    }

    public function exportsObjectReachedAgainThroughArrayAsClassOnly(): void
    {
        $value = new \stdClass();
        $value->items = [$value, 'item'];

        $result = DefaultProcessor::createDefault()($value);

        Assert::same($result, ['@class' => 'stdClass', 'items' => [['@class' => 'stdClass'], 'item']]);
    }

    public function exportsSharedObjectInEveryPlace(): void
    {
        $shared = (object) ['id' => 1];
        $value = (object) ['first' => $shared, 'second' => $shared];

        $result = DefaultProcessor::createDefault()($value);

        Assert::same($result, [
            '@class' => 'stdClass',
            'first' => ['@class' => 'stdClass', 'id' => 1],
            'second' => ['@class' => 'stdClass', 'id' => 1],
        ]);
    }

    public function exportsObjectFullyAgainAfterProcessorFailed(): void
    {
        $value = (object) ['id' => 1];
        try {
            $this->processor->process($value, static fn(mixed $v): never => throw new \RuntimeException('processor failed'));
        } catch (\RuntimeException) {
        }

        $result = $this->processor->process($value, static fn(mixed $v): mixed => $v);

        Assert::same($result, ['@class' => 'stdClass', 'id' => 1]);
    }

    #[BeforeTest]
    protected function setUp(): void
    {
        $this->processor = new FallbackProcessor();
    }
}
